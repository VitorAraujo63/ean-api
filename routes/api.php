<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController; // ✅ CORRETO
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductExportController;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\CustomersController;
use App\Http\Controllers\CategoryExportController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;


Route::get('/test-export-csv', [ProductExportController::class, 'exportCsv']);


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::delete('/produtos/{id}', [ProductController::class, 'destroy']);




Route::post('/produto', [ProductController::class, 'buscarPorEan']);


Route::get('/produtos', [ProductController::class, 'index']);
Route::get('/produtos/{id}', [ProductController::class, 'show']);


Route::post('/produtos', [ProductController::class, 'store']);
Route::put('/produtos/{id}', [ProductController::class, 'update']);



Route::get('/produtos/export/csv', [ProductExportController::class, 'exportCsv']);


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





Route::middleware('auth:sanctum')->get('/debug-user', function (Request $request) {
        return response()->json([
            'user' => $request->user()
        ]);
    });


    // ✅ Listagem e visualização de categorias
Route::get('/categorias', [CategoryController::class, 'index']);
Route::get('/categorias/{id}', [CategoryController::class, 'show']);
Route::get('/categorias/stats', [CategoryController::class, 'stats']);

    // ✅ Criar e atualizar categorias
Route::post('/categorias', [CategoryController::class, 'store']);
Route::put('/categorias/{id}', [CategoryController::class, 'update']);
Route::patch('/categorias/{id}/toggle-status', [CategoryController::class, 'toggleStatus']);

    // ❌ Deletar categoria
Route::delete('/categorias/{id}', [CategoryController::class, 'destroy'])->middleware('role:admin');

    // ✅ Exportar categorias
Route::get('/categorias/export/csv', [CategoryExportController::class, 'exportCsv']);


    // ✅ Dashboard data (any authenticated user)
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/dashboard/quick-stats', [DashboardController::class, 'quickStats']);

    // ✅ Analytics (admin and operador)

Route::get('/analytics/category/{categoryId}', [AnalyticsController::class, 'categoryAnalytics']);
Route::get('/analytics/profit', [AnalyticsController::class, 'profitAnalysis']);
