<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DailyCostController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ItemMovementController;
use App\Http\Controllers\Api\ItemVariantController;
use App\Http\Controllers\Api\KitchenOrderController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReferenceTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/user/password', [AuthController::class, 'updatePassword']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::get('/items', [ItemController::class, 'index']);
        Route::post('/items', [ItemController::class, 'store']);
        Route::put('/items/{item}', [ItemController::class, 'update']);
        Route::delete('/items/{item}', [ItemController::class, 'destroy']);

        Route::get('/item-variants', [ItemVariantController::class, 'index']);
        Route::post('/item-variants', [ItemVariantController::class, 'store']);
        Route::put('/item-variants/{itemVariant}', [ItemVariantController::class, 'update']);
        Route::delete('/item-variants/{itemVariant}', [ItemVariantController::class, 'destroy']);

        Route::get('/item-movements', [ItemMovementController::class, 'index']);
        Route::post('/item-movements', [ItemMovementController::class, 'store']);
        Route::post('/item-movements/bulk', [ItemMovementController::class, 'storeBulk']);
        Route::put('/item-movements/{movement}', [ItemMovementController::class, 'update']);
        Route::delete('/item-movements/{movement}', [ItemMovementController::class, 'destroy']);

        Route::get('/customers', [CustomerController::class, 'index']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::put('/customers/{customer}', [CustomerController::class, 'update']);
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);

        Route::get('/reference-types', [ReferenceTypeController::class, 'index']);
        Route::post('/reference-types', [ReferenceTypeController::class, 'store']);
        Route::put('/reference-types/{referenceType}', [ReferenceTypeController::class, 'update']);
        Route::delete('/reference-types/{referenceType}', [ReferenceTypeController::class, 'destroy']);

        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard/trends', [DashboardController::class, 'trends']);
        Route::get('/dashboard/top-selling-variants', [DashboardController::class, 'topSellingVariants']);
        Route::get('/dashboard/daily-report', [DashboardController::class, 'dailyReport']);

        // Daily costs
        Route::get('/daily-costs', [DailyCostController::class, 'show']);
        Route::post('/daily-costs', [DailyCostController::class, 'store']);

        // Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders/{order}/pay', [OrderController::class, 'pay']);
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

        // Kitchen
        Route::get('/kitchen/orders', [KitchenOrderController::class, 'index']);
        Route::post('/kitchen/orders/{order}/ready', [KitchenOrderController::class, 'markReady']);
        Route::get('/kitchen/orders/ready', [KitchenOrderController::class, 'ready']);
    });
});
