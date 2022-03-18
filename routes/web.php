<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\LegacyProductsController;

Route::get('/', function () {
    return view('welcome');
});

// Legacy Routes
Route::get('legacy/products', [LegacyProductsController::class, 'index']);
Route::get('legacy/products/{section}', [LegacyProductsController::class, 'show']);

// Refactored Routes
Route::get('/products', [ProductsController::class, 'index']);
Route::get('/products/{section}', [ProductsController::class, 'show']);

