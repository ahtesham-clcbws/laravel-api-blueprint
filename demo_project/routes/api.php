<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;

use App\Http\Middleware\AuthenticateApiToken;

// Public Authentication API
Route::post('auth/register', [AuthController::class, 'register'])->name('api.auth.register');
Route::post('auth/login', [AuthController::class, 'login'])->name('api.auth.login');

// Protected API Routes
Route::middleware(AuthenticateApiToken::class)->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::get('auth/user', [AuthController::class, 'user'])->name('api.auth.user');
    
    Route::get('orders', [OrderController::class, 'index'])->name('api.orders.index');
    Route::post('orders', [OrderController::class, 'store'])->name('api.orders.store');
});

// Public E-commerce API
Route::get('products', [ProductController::class, 'index'])->name('api.products.index');
Route::post('products', [ProductController::class, 'store'])->name('api.products.store');
