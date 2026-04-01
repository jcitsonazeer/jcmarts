<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SubCategoryController;

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/sub-categories', [SubCategoryController::class, 'index']);
Route::get('/banners', [CatalogController::class, 'banners']);
Route::get('/featured-products', [CatalogController::class, 'featuredProducts']);
Route::get('/offers', [CatalogController::class, 'offers']);

Route::get('/test', function () {
return response()->json([
        'status' => true,
        'message' => 'API working successfully'
    ]);
});
