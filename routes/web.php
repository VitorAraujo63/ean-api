<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/teste-export-csv', function() {
    return view('/test/test-export-csv');
});

Route::get('/login', function() {
    return view('/test/login');
});
