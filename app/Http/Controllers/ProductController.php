<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;


/**
 * @OA\Tag(
 *     name="Produtos",
 *     description="Operações relacionadas aos produtos."
 * )
 */
class ProductController extends Controller
{
    public function buscarPorEan(Request $request)
    {
        $ean = $request->input('ean');

        if (!$ean) return $this->errorResponse('EAN não informado.', 400);

        $produto = Product::where('ean', $ean)->first();
        if ($produto) return $this->successResponse(new ProductResource($produto));

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

            LogHelper::log('created_product', "Produto '{$produto->description}' (ID: {$produto->id}) criado automaticamente via Cosmos.");

            return $this->successResponse(new ProductResource($produto), 'Produto criado com sucesso.', 201);
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

            LogHelper::log('created_product', "Produto '{$produto->description}' (ID: {$produto->id}) criado automaticamente via Open Foods.");

            return $this->successResponse(new ProductResource($produto), 'Produto criado com sucesso.', 201);
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

                LogHelper::log('created_product', "Produto '{$produto->description}' (ID: {$produto->id}) criado automaticamente via Google Books.");

                return $this->successResponse(new ProductResource($produto), 'Produto criado com sucesso.', 201);
            }
        }

        return $this->errorResponse('Produto não encontrado em nenhuma fonte.', 404);
    }

    /**
     * @OA\Get(
     *     path="/api/produtos",
     *     tags={"Produtos"},
     *     summary="Listar todos os produtos",
     *     @OA\Response(response=200, description="Lista de produtos"),
     *     @OA\Response(response=500, description="Erro interno no servidor.")
     * )
     */
    public function index()
    {
        return ProductResource::collection(Product::all());
    }

    /**
     * @OA\Get(
     *     path="/api/produtos/{id}",
     *     tags={"Produtos"},
     *     summary="Exibir detalhes de um produto",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Detalhes do produto"),
     *     @OA\Response(response=404, description="Produto não encontrado")
     * )
     */
    public function show($id)
    {
        $produto = Product::find($id);
        if (!$produto) return response()->json(['error' => 'Produto não encontrado.'], 404);

        return response()->json($produto);
    }


    /**
     * @OA\Post(
     *     path="/api/produtos",
     *     tags={"Produtos"},
     *     summary="Criar um novo produto",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"ean"},
     *                 @OA\Property(property="ean", type="string", example="1234567890123"),
     *                 @OA\Property(property="description", type="string", example="Produto de Exemplo"),
     *                 @OA\Property(property="brand", type="string", example="Marca XYZ"),
     *                 @OA\Property(property="image", type="string", example="https://example.com/image.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Produto criado com sucesso."),
     *     @OA\Response(response=400, description="Erro ao validar os dados.")
     * )
     */
    public function store(Request $request)
    {
        Log::info('Teste de criação de log', ['context' => 'teste']);
        $validated = $request->validate([
            'ean' => 'required|string|unique:products,ean',
            'description' => 'nullable|string',
            'brand' => 'nullable|string',
            'ncm' => 'nullable|string',
            'unit' => 'nullable|string',
            'gross_weight' => 'nullable|numeric',
            'net_weight' => 'nullable|numeric',
            'image' => 'nullable|string',
            'source' => 'nullable|string',
            'type' => 'nullable|string',
            'complete' => 'nullable|boolean',
            'price' => 'nullable|numeric',
            'cost' => 'nullable|numeric',
        ]);

        $produto = Product::create($validated);
        Log::channel('activity')->info('Produto criado com sucesso', ['product_id' => $produto->id]);
        return new ProductResource($produto);
    }


    /**
     * @OA\Put(
     *     path="/api/produtos/{id}",
     *     tags={"Produtos"},
     *     summary="Atualizar um produto",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="description", type="string", example="Produto Atualizado")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Produto atualizado com sucesso."),
     *     @OA\Response(response=404, description="Produto não encontrado.")
     * )
     */
    public function update(Request $request, $id)
    {
        $produto = Product::find($id);
        if (!$produto) return response()->json(['error' => 'Produto não encontrado.'], 404);

        $validated = $request->validate([
            'ean' => "required|string|unique:products,ean,{$id}",
            'description' => 'nullable|string',
            'brand' => 'nullable|string',
            'ncm' => 'nullable|string',
            'unit' => 'nullable|string',
            'gross_weight' => 'nullable|numeric',
            'net_weight' => 'nullable|numeric',
            'image' => 'nullable|string',
            'source' => 'nullable|string',
            'type' => 'nullable|string',
            'complete' => 'nullable|boolean',
            'price' => 'nullable|numeric',
            'cost' => 'nullable|numeric'
        ]);

        $produto->update($validated);

        LogHelper::log('updated_product', "Produto '{$produto->description}' (ID: {$produto->id}) atualizado.");

        return response()->json($produto);

    }


    /**
     * @OA\Delete(
     *     path="/api/produtos/{id}",
     *     tags={"Produtos"},
     *     summary="Excluir um produto",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Produto excluído com sucesso."),
     *     @OA\Response(response=404, description="Produto não encontrado.")
     * )
     */
        public function destroy($id)
    {
        $produto = Product::find($id);

        if (!$produto) {
            return response()->json(['message' => 'Produto não encontrado'], 404);
        }

        $produto->delete();

        return response()->json(['message' => 'Produto excluído com sucesso']);
    }

    private function successResponse($data, $message = null, $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    private function errorResponse($message = 'Erro inesperado.', $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
        ], $code);
    }

    public function createImage(Request $request)
    {
        $request->validate([
            'imagem' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('imagem')) {
            $nomeArquivo = time() . '.' . $request->imagem->extension();
            $caminho = $request->imagem->storeAs('images', $nomeArquivo, 'public');

            // Você pode salvar o caminho no banco de dados se quiser
            // Exemplo: Imagem::create(['caminho' => $caminho]);

            return back()->with('success', 'Imagem salva com sucesso!')->with('caminho', $caminho);
        }

        return back()->with('error', 'Falha ao enviar imagem.');
    }

    public function showImages()
    {
        $files = Storage::disk('public')->files('images'); // folder inside storage/app/public
        return view('test.images', compact('files'));
    }
}
