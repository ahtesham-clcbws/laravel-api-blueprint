<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;

Route::get('/', function () {
    return view('welcome');
});

// Authentication API
Route::post('/api/auth/register', [AuthController::class, 'register'])->name('api.auth.register');
Route::post('/api/auth/login', [AuthController::class, 'login'])->name('api.auth.login');
Route::post('/api/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
Route::get('/api/auth/user', [AuthController::class, 'user'])->name('api.auth.user');

// E-commerce API (Relational data)
Route::get('/api/products', [ProductController::class, 'index'])->name('api.products.index');
Route::post('/api/products', [ProductController::class, 'store'])->name('api.products.store');
Route::get('/api/orders', [OrderController::class, 'index'])->name('api.orders.index');
Route::post('/api/orders', [OrderController::class, 'store'])->name('api.orders.store');
