<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\FrontendProductController;
use App\Http\Controllers\SingleProductController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\UomMasterController;
use App\Http\Controllers\RateMasterController;
use App\Http\Controllers\IndexBannerController;

Route::get('/', [FrontendController::class, 'index'])->name('frontend.home');
Route::get('/products', [FrontendProductController::class, 'index'])->name('frontend.products');
Route::get('/single-product', [SingleProductController::class, 'show'])->name('frontend.single_product');
Route::get('/cart', [FrontendController::class, 'cart'])->name('frontend.cart');
Route::get('/checkout', [FrontendController::class, 'checkout'])->name('frontend.checkout');

// Optional compatibility for old template URLs
Route::redirect('/index.html', '/');
Route::redirect('/category.html', '/category');
Route::redirect('/product.html', '/product');
Route::redirect('/cart.html', '/cart');
Route::redirect('/checkout.html', '/checkout');

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.auth')->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('sub-categories', SubCategoryController::class);
        Route::resource('products', ProductController::class);
        Route::get('product-images', [ProductImageController::class, 'index'])->name('product-images.index');
        Route::get('product-images/create', [ProductImageController::class, 'create'])->name('product-images.create');
        Route::post('product-images', [ProductImageController::class, 'store'])->name('product-images.store');
        Route::get('product-images/{productId}/edit', [ProductImageController::class, 'edit'])->name('product-images.edit');
        Route::put('product-images/{productId}', [ProductImageController::class, 'update'])->name('product-images.update');
        Route::delete('product-images/{productId}', [ProductImageController::class, 'destroy'])->name('product-images.destroy');
        Route::resource('index-banners', IndexBannerController::class);
        Route::resource('uom-masters', UomMasterController::class);
        Route::resource('rate-masters', RateMasterController::class);
        Route::get('selected-display', [RateMasterController::class, 'selectedDisplayIndex'])->name('selected-display.index');
        Route::get('selected-display/{productId}', [RateMasterController::class, 'selectedDisplayEdit'])->name('selected-display.edit');
        Route::post('selected-display/{productId}', [RateMasterController::class, 'selectedDisplayUpdate'])->name('selected-display.update');

        Route::resource('users', AdminUserController::class)->except(['destroy']);
        Route::patch('users/{id}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status');
    });
});
