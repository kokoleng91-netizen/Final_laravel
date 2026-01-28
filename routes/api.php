<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Add this OUTSIDE the admin middleware group (so it's publicly accessible)
Route::get('/categories', [CategoriesController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Orders (customer)
    Route::get('/orders', [OrderController::class, 'index']);   // my orders
    Route::post('/orders', [OrderController::class, 'store']);  // place order
    Route::get('/orders/{id}', [OrderController::class, 'show']); // order detail
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    // Products
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Categories
    Route::post('/categories', [CategoriesController::class, 'store']);
    Route::put('/categories/{id}', [CategoriesController::class, 'update']);
    Route::delete('/categories/{id}', [CategoriesController::class, 'destroy']);

    // Orders (admin)
    Route::get('/admin/orders', [OrderController::class, 'adminIndex']);
    Route::put('/admin/orders/{id}', [OrderController::class, 'updateStatus']);

    // Users (admin)
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Add GET route for categories (can be public or admin-only)
    Route::get('/categories', [CategoriesController::class, 'index']);

    // Add Order Items routes if you need them
    Route::get('/order-items', [OrderItemController::class, 'index']);

    Route::get('/admin/orders', [OrderController::class, 'adminIndex']);
});
