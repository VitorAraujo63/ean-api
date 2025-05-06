<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductExportController;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

// Rotas de testes sem usuários
Route::get('/test-export-csv', [ProductExportController::class, 'exportCsv']);


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


    Route::middleware(['auth:sanctum', 'role:admin'])->get('/logs', function () {
        $path = storage_path('logs/activity.log');

        if (!File::exists($path)) {
            return response()->json(['message' => 'Nenhum log encontrado.'], 404);
        }

        $logs = File::get($path);

        return Response::make($logs, 200, [
            'Content-Type' => 'text/plain',
        ]);
    });

    Route::middleware(['auth:sanctum', 'role:admin'])->get('/logs/db', [ActivityLogController::class, 'index']);
});




Route::middleware('auth:sanctum')->get('/debug-user', function (Request $request) {
    return response()->json([
        'user' => $request->user()
    ]);
});
