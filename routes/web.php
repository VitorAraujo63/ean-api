<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/teste-export-csv', function() {
    return view('/test/test-export-csv');
});

Route::get('/login', function() {
    return view('/test/login');
});

Route::get('/images', function() {
    return view('/test/images');
});

Route::post('/images', [ProductController::class, 'createImage'])->name('imagens.store');