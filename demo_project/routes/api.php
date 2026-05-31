<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\EmployeeController;

use App\Http\Middleware\AuthenticateApiToken;

// Version v1 API Group
Route::prefix('v1')->group(function () {

    // Public Authentication API
    Route::post('auth/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('api.auth.login');

    // Protected API Routes
    Route::middleware(AuthenticateApiToken::class)->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('auth/user', [AuthController::class, 'user'])->name('api.auth.user');
        
        Route::get('orders', [OrderController::class, 'index'])->name('api.orders.index');
        Route::post('orders', [OrderController::class, 'store'])->name('api.orders.store');

        // Protected Employee API
        Route::get('employees', [EmployeeController::class, 'index'])->name('api.employees.index');
        Route::post('employees', [EmployeeController::class, 'store'])->name('api.employees.store');
        Route::get('employees/{id}', [EmployeeController::class, 'show'])->name('api.employees.show');
        Route::put('employees/{id}', [EmployeeController::class, 'update'])->name('api.employees.update');
        Route::delete('employees/{id}', [EmployeeController::class, 'destroy'])->name('api.employees.destroy');
    });

    // Public E-commerce API
    Route::get('products', [ProductController::class, 'index'])->name('api.products.index');
    Route::post('products', [ProductController::class, 'store'])->name('api.products.store');

});

// Version v1 API Group
Route::prefix('v2')->group(function () {

    // Public Authentication API
    Route::post('auth/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('api.auth.login');

    // Protected API Routes
    Route::middleware(AuthenticateApiToken::class)->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('auth/user', [AuthController::class, 'user'])->name('api.auth.user');
        
        Route::get('orders', [OrderController::class, 'index'])->name('api.orders.index');
        Route::post('orders', [OrderController::class, 'store'])->name('api.orders.store');

        // Protected Employee API
        Route::get('employees', [EmployeeController::class, 'index'])->name('api.employees.index');
        Route::post('employees', [EmployeeController::class, 'store'])->name('api.employees.store');
        Route::get('employees/{id}', [EmployeeController::class, 'show'])->name('api.employees.show');
        Route::put('employees/{id}', [EmployeeController::class, 'update'])->name('api.employees.update');
        Route::delete('employees/{id}', [EmployeeController::class, 'destroy'])->name('api.employees.destroy');
    });

    // Public E-commerce API
    Route::get('products', [ProductController::class, 'index'])->name('api.products.index');
    Route::post('products', [ProductController::class, 'store'])->name('api.products.store');

});
