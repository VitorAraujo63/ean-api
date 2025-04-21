<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductExportController;


// 🔓 Rotas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 🔐 Rotas protegidas por autenticação
Route::middleware(['auth:sanctum'])->group(function () {

    // ✅ Buscar produto por EAN (admin e operador)
    Route::post('/produto', [ProductController::class, 'buscarPorEan'])->middleware('role:admin,operador');

    // ✅ Listagem e visualização de produtos (qualquer usuário autenticado)
    Route::get('/produtos', [ProductController::class, 'index']);
    Route::get('/produtos/{id}', [ProductController::class, 'show']);

    // ✅ Criar e atualizar produtos (admin e operador)
    Route::post('/produtos', [ProductController::class, 'store'])->middleware('role:admin,operador');
    Route::put('/produtos/{id}', [ProductController::class, 'update'])->middleware('role:admin,operador');

    // ❌ Deletar produto (somente admin)
    Route::delete('/produtos/{id}', [ProductController::class, 'destroy'])->middleware('role:admin');

    // ✅ Dados do usuário autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::middleware(['auth:sanctum', 'role:admin,operador'])->get('/produtos/export/csv', [ProductExportController::class, 'exportCsv']);
});




Route::middleware('auth:sanctum')->get('/debug-user', function (Request $request) {
    return response()->json([
        'user' => $request->user()
    ]);
});
