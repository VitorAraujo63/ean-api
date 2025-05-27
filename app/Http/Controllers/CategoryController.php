<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Resources\CategoryResource;
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Categorias",
 *     description="Operações relacionadas às categorias de produtos."
 * )
 */
class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/categorias",
     *     tags={"Categorias"},
     *     summary="Listar todas as categorias",
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Pesquisar por nome da categoria",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por status",
     *         @OA\Schema(type="string", enum={"ativo", "inativo"})
     *     ),
     *     @OA\Response(response=200, description="Lista de categorias"),
     *     @OA\Response(response=500, description="Erro interno no servidor.")
     * )
     */
    public function index(Request $request)
    {
        $query = Category::withCount('products');

        // Search by name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $categories = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories->items()),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/categorias/{id}",
     *     tags={"Categorias"},
     *     summary="Exibir detalhes de uma categoria",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Detalhes da categoria"),
     *     @OA\Response(response=404, description="Categoria não encontrada")
     * )
     */
    public function show($id)
    {
        $category = Category::withCount('products')->with('products')->find($id);

        if (!$category) {
            return $this->errorResponse('Categoria não encontrada.', 404);
        }

        return $this->successResponse(new CategoryResource($category));
    }

    /**
     * @OA\Post(
     *     path="/api/categorias",
     *     tags={"Categorias"},
     *     summary="Criar uma nova categoria",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"name"},
     *                 @OA\Property(property="name", type="string", example="Eletrônicos"),
     *                 @OA\Property(property="description", type="string", example="Produtos eletrônicos diversos"),
     *                 @OA\Property(property="status", type="string", enum={"ativo", "inativo"}, example="ativo"),
     *                 @OA\Property(property="image", type="string", example="https://example.com/image.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Categoria criada com sucesso."),
     *     @OA\Response(response=400, description="Erro ao validar os dados.")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'status' => 'nullable|in:ativo,inativo',
            'image' => 'nullable|string|url'
        ]);

        $validated['status'] = $validated['status'] ?? 'ativo';

        $category = Category::create($validated);

        LogHelper::log('created_category', "Categoria '{$category->name}' (ID: {$category->id}) criada com sucesso.");

        return $this->successResponse(
            new CategoryResource($category->loadCount('products')),
            'Categoria criada com sucesso.',
            201
        );
    }

    /**
     * @OA\Put(
     *     path="/api/categorias/{id}",
     *     tags={"Categorias"},
     *     summary="Atualizar uma categoria",
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
     *                 @OA\Property(property="name", type="string", example="Eletrônicos Atualizados"),
     *                 @OA\Property(property="description", type="string", example="Descrição atualizada"),
     *                 @OA\Property(property="status", type="string", enum={"ativo", "inativo"})
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Categoria atualizada com sucesso."),
     *     @OA\Response(response=404, description="Categoria não encontrada.")
     * )
     */
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->errorResponse('Categoria não encontrada.', 404);
        }

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($id)
            ],
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:ativo,inativo',
            'image' => 'sometimes|nullable|string|url'
        ]);

        $category->update($validated);

        LogHelper::log('updated_category', "Categoria '{$category->name}' (ID: {$category->id}) atualizada.");

        return $this->successResponse(
            new CategoryResource($category->loadCount('products')),
            'Categoria atualizada com sucesso.'
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/categorias/{id}",
     *     tags={"Categorias"},
     *     summary="Excluir uma categoria",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Categoria excluída com sucesso."),
     *     @OA\Response(response=404, description="Categoria não encontrada."),
     *     @OA\Response(response=400, description="Categoria possui produtos vinculados.")
     * )
     */
    public function destroy($id)
    {
        $category = Category::withCount('products')->find($id);

        if (!$category) {
            return $this->errorResponse('Categoria não encontrada.', 404);
        }

        // Check if category has products
        if ($category->products_count > 0) {
            return $this->errorResponse(
                'Não é possível excluir uma categoria que possui produtos vinculados. Remova os produtos primeiro ou altere a categoria deles.',
                400
            );
        }

        $categoryName = $category->name;
        $category->delete();

        LogHelper::log('deleted_category', "Categoria '{$categoryName}' (ID: {$id}) excluída.");

        return $this->successResponse(null, 'Categoria excluída com sucesso.');
    }

    /**
     * Get categories statistics
     */
    public function stats()
    {
        $totalCategories = Category::count();
        $activeCategories = Category::active()->count();
        $inactiveCategories = Category::inactive()->count();
        $categoriesWithProducts = Category::has('products')->count();

        return $this->successResponse([
            'total_categories' => $totalCategories,
            'active_categories' => $activeCategories,
            'inactive_categories' => $inactiveCategories,
            'categories_with_products' => $categoriesWithProducts,
            'categories_without_products' => $totalCategories - $categoriesWithProducts
        ]);
    }

    /**
     * Toggle category status
     */
    public function toggleStatus($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->errorResponse('Categoria não encontrada.', 404);
        }

        $newStatus = $category->status === 'ativo' ? 'inativo' : 'ativo';
        $category->update(['status' => $newStatus]);

        LogHelper::log('toggled_category_status', "Status da categoria '{$category->name}' (ID: {$category->id}) alterado para '{$newStatus}'.");

        return $this->successResponse(
            new CategoryResource($category->loadCount('products')),
            "Status da categoria alterado para '{$newStatus}' com sucesso."
        );
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
}
