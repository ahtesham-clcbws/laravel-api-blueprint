<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/api/products', [\App\Http\Controllers\ProductController::class, 'store'])->name('api.products.store');
Route::post('/api/orders', [\App\Http\Controllers\OrderController::class, 'store'])->name('api.orders.store');
