<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function buscarPorEan(Request $request)
    {
        $ean = $request->input('ean');

        if (!$ean) return response()->json(['error' => 'EAN não informado.'], 400);

        $produto = Product::where('ean', $ean)->first();
        if ($produto) return response()->json($produto);

        // 1️⃣ Consulta no Cosmos
        $cosmos = Http::withHeaders([
            'X-Cosmos-Token' => config('services.cosmos.key')
        ])->get("https://api.cosmos.bluesoft.com.br/gtins/{$ean}");

        if ($cosmos->successful()) {
            $dados = $cosmos->json();

            $produto = Product::create([
                'ean' => $ean,
                'description' => $dados['description'] ?? null,
                'brand' => $dados['brand']['name'] ?? null,
                'ncm' => $dados['ncm']['code'] ?? null,
                'unit' => $dados['gtins'][0]['commercial_unit']['type_packaging'] ?? null,
                'net_weight' => $dados['net_weight'] ?? null,
                'gross_weight' => $dados['gross_weight'] ?? null,
                'image' => $dados['barcode_image'] ?? null,
                'source' => 'cosmos',
                'type' => 'mercado',
                'complete' => true
            ]);

            return response()->json($produto);
        }

        // 2️⃣ Open Food Facts
        $openfood = Http::get("https://world.openfoodfacts.org/api/v0/product/{$ean}.json");

        if ($openfood->successful() && $openfood->json('status') === 1) {
            $dados = $openfood->json('product');

            $produto = Product::create([
                'ean' => $ean,
                'description' => $dados['product_name'] ?? null,
                'brand' => $dados['brands'] ?? null,
                'unit' => $dados['quantity'] ?? null,
                'image' => $dados['image_url'] ?? null,
                'source' => 'openfoodfacts',
                'type' => 'alimento',
                'complete' => false
            ]);

            return response()->json($produto);
        }

        // 3️⃣ Livros
        if (str_starts_with($ean, '978') || str_starts_with($ean, '979')) {
            $google = Http::get("https://www.googleapis.com/books/v1/volumes?q=isbn:{$ean}");

            if ($google->successful() && isset($google['items'][0]['volumeInfo'])) {
                $info = $google['items'][0]['volumeInfo'];

                $produto = Product::create([
                    'ean' => $ean,
                    'description' => $info['title'] ?? null,
                    'brand' => implode(', ', $info['authors'] ?? []),
                    'unit' => $info['pageCount'] ? "{$info['pageCount']} páginas" : null,
                    'image' => $info['imageLinks']['thumbnail'] ?? null,
                    'source' => 'googlebooks',
                    'type' => 'livro',
                    'complete' => false
                ]);

                return response()->json($produto);
            }
        }

        return response()->json(['error' => 'Produto não encontrado em nenhuma fonte.'], 404);
    }
}
