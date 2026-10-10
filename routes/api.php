<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum', 'active']);
});

Route::middleware(['auth:sanctum', 'active'])->group(function () {

    Route::get('stores', [StoreController::class, 'index']);
    Route::get('stores/{store}', [StoreController::class, 'show']);
    Route::post('stores', [StoreController::class, 'store']);
    Route::put('stores/{store}', [StoreController::class, 'update']);
    Route::post('stores/{id}/suspend', [StoreController::class, 'suspend']);
    Route::post('stores/{id}/unsuspend', [StoreController::class, 'unsuspend']);

    Route::get('stores/{store}/products', [ProductController::class, 'indexByStore']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::post('stores/{store}/products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update']);
    Route::post('products/{product}/toggle-availability', [ProductController::class, 'toggleAvailability']);

    Route::post('orders', [OrderController::class, 'place']);
    Route::get('orders/my', [OrderController::class, 'myOrders']);
    Route::get('orders/store', [OrderController::class, 'storeOrders']);
    Route::get('orders/driver', [OrderController::class, 'driverOrders']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders/{order}/accept', [OrderController::class, 'accept']);
    Route::post('orders/{order}/assign-driver', [OrderController::class, 'assignDriver']);
    Route::post('orders/{order}/picked-up', [OrderController::class, 'markPickedUp']);
    Route::post('orders/{order}/delivered', [OrderController::class, 'markDelivered']);
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
});
