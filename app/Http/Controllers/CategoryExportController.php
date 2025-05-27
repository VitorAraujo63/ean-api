<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryExportController extends Controller
{
    public function exportCsv(Request $request): JsonResponse
    {
        $query = Category::withCount('products');

        // Apply same filters as index method
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $categories = $query->orderBy('name', 'asc')->get();

        $exportData = $categories->map(function ($category) {
            return [
                'ID' => $category->id,
                'Nome' => $category->name,
                'Descrição' => $category->description ?? 'N/A',
                'Status' => ucfirst($category->status),
                'Produtos' => $category->products_count,
                'Data de Criação' => $category->created_at->format('d/m/Y H:i:s'),
                'Última Atualização' => $category->updated_at->format('d/m/Y H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $exportData,
            'filename' => 'categorias_' . date('Y-m-d_H-i-s') . '.csv'
        ]);
    }
}
