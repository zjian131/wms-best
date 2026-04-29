<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\OrderController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::apiResource('stores', StoreController::class);
    Route::post('stores/{store}/sync', [StoreController::class, 'sync']);
    Route::post('stores/sync/all', [StoreController::class, 'syncAll']);
    Route::get('platforms/list', [StoreController::class, 'platforms']);

    Route::apiResource('products', ProductController::class);
    Route::post('products/{product}/bind-order', [ProductController::class, 'bindOrder']);
    Route::post('products/{product}/update-stock', [ProductController::class, 'updateStock']);
    Route::post('products/{product}/move-warehouse', [ProductController::class, 'moveWarehouse']);

    Route::apiResource('warehouses', WarehouseController::class);
    Route::post('warehouses/{warehouse}/set-default', [WarehouseController::class, 'setDefault']);

    Route::apiResource('orders', OrderController::class);
    Route::get('returns', [OrderController::class, 'returns']);
    Route::get('returns/{return}', [OrderController::class, 'showReturn']);
    Route::put('returns/{return}', [OrderController::class, 'updateReturn']);
    Route::post('returns/{return}/mark-received', [OrderController::class, 'markAsReceived']);
    Route::post('returns/{return}/mark-restocked', [OrderController::class, 'markAsRestocked']);
});
