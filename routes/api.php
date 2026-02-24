<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SubCategoryController;

Route::get('/products', [ProductController::class, 'index']);
Route::get('/sub-categories', [SubCategoryController::class, 'index']);

Route::get('/test', function () {
    return response()->json([
        'status' => true,
        'message' => 'API working successfully'
    ]);
});