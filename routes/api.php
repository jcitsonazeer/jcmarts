<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SubCategoryController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderController;

Route::prefix('v1')->group(function () {
    Route::post('/otp/send', [AuthController::class, 'sendOtp']);
    Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);
    Route::post('/register/otp/send', [AuthController::class, 'sendRegisterOtp']);
    Route::post('/register/otp/verify', [AuthController::class, 'verifyRegisterOtp']);

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

    // Cart routes — work for BOTH guests (with X-Device-ID header) and authenticated users
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{cartId}', [CartController::class, 'update'])->whereNumber('cartId');
    Route::delete('/cart/{cartId}', [CartController::class, 'destroy'])->whereNumber('cartId');
    Route::get('/cart/count', [CartController::class, 'count']);

    // Auth-required routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        // Cart merge — transfer guest cart to customer cart after login
        Route::post('/cart/merge', [CartController::class, 'merge']);

        // Wishlist routes (login required)
        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist', [WishlistController::class, 'store']);
        Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy'])->whereNumber('productId');
        Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
        Route::get('/wishlist/check/{productId}', [WishlistController::class, 'check'])->whereNumber('productId');
        Route::get('/wishlist/count', [WishlistController::class, 'count']);

        // ─── Profile & Addresses ────────────────────────────
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);

        Route::get('/serviceable-pincodes', [ProfileController::class, 'serviceablePincodes']);

        Route::get('/addresses', [ProfileController::class, 'addressIndex']);
        Route::post('/addresses', [ProfileController::class, 'addressStore']);
        Route::put('/addresses/{addressId}', [ProfileController::class, 'addressUpdate'])->whereNumber('addressId');
        Route::delete('/addresses/{addressId}', [ProfileController::class, 'addressDestroy'])->whereNumber('addressId');

        // ─── Checkout & Payment ─────────────────────────────
        Route::get('/checkout', [CheckoutController::class, 'index']);
        Route::post('/payment/create-order', [CheckoutController::class, 'createOrder']);
        Route::post('/payment/verify', [CheckoutController::class, 'verifyPayment']);
        Route::post('/payment/release', [CheckoutController::class, 'releasePendingPayment']);

        // ─── Orders, Cancellation & Returns ─────────────────
        // IMPORTANT: fixed routes must come BEFORE {id} routes
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/returns/reasons', [OrderController::class, 'returnReasons']);
        Route::get('/orders/{orderId}', [OrderController::class, 'show'])->whereNumber('orderId');
        Route::post('/orders/{orderId}/cancel', [OrderController::class, 'cancel'])->whereNumber('orderId');
        Route::post('/orders/{orderId}/return', [OrderController::class, 'requestReturn'])->whereNumber('orderId');
    });
});

Route::get('/test', function () {
    return response()->json([
        'status' => true,
        'message' => 'API working successfully',
    ]);
});
