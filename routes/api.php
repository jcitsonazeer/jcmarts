<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SubCategoryController;

Route::prefix('v1')->group(function () {
    Route::post('/otp/send', [AuthController::class, 'sendOtp']);
    Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);

    Route::get('/home', [CatalogController::class, 'home']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/brands', [BrandController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id');
    Route::get('/sub-categories', [SubCategoryController::class, 'index']);
    Route::get('/banners', [CatalogController::class, 'banners']);
    Route::get('/featured-products', [CatalogController::class, 'featuredProducts']);
    Route::get('/offers', [CatalogController::class, 'offers']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::get('/test', function () {
return response()->json([
        'status' => true,
        'message' => 'API working successfully'
    ]);
});
