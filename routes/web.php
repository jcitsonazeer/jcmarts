<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\FrontendCartController;
use App\Http\Controllers\FrontendCheckoutController;
use App\Http\Controllers\FrontendProductController;
use App\Http\Controllers\SingleProductController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\UomMasterController;
use App\Http\Controllers\RateMasterController;
use App\Http\Controllers\IndexBannerController;
use App\Http\Controllers\OfferDetailController;
use App\Http\Controllers\OfferProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\StockInfoController;
use App\Http\Controllers\FrontendWishlistController;

Route::get('/', [FrontendController::class, 'index'])->name('frontend.home');
Route::get('/products', [FrontendProductController::class, 'index'])->name('frontend.products');
Route::get('/single-product', [SingleProductController::class, 'show'])->name('frontend.single_product');
Route::get('/cart', [FrontendCartController::class, 'index'])->name('frontend.cart');
Route::post('/cart/{cartId}/quantity', [FrontendCartController::class, 'updateQuantity'])->name('frontend.cart.update');
Route::post('/cart/{cartId}/remove', [FrontendCartController::class, 'remove'])->name('frontend.cart.remove');
Route::get('/checkout', [FrontendCheckoutController::class, 'index'])->name('frontend.checkout');
Route::get('/wishlist', [FrontendWishlistController::class, 'index'])->name('frontend.wishlist');
Route::post('/checkout/proceed', [FrontendCheckoutController::class, 'proceedToPayment'])->name('frontend.checkout.proceed');
Route::get('/payment', [FrontendCheckoutController::class, 'payment'])->name('frontend.payment');
Route::post('/payment/create-order', [FrontendCheckoutController::class, 'createRazorpayOrder'])->name('frontend.payment.create_order');
Route::post('/payment/verify', [FrontendCheckoutController::class, 'verifyRazorpayPayment'])->name('frontend.payment.verify');
Route::get('/register', [CustomerAuthController::class, 'register'])->name('frontend.register');
Route::post('/register', [CustomerAuthController::class, 'storeRegister'])->name('frontend.register.store');
Route::get('/login', [CustomerAuthController::class, 'login'])->name('frontend.login');
Route::post('/login', [CustomerAuthController::class, 'storeLogin'])->name('frontend.login.store');
Route::get('/login/otp', [CustomerAuthController::class, 'showLoginOtp'])->name('frontend.login.otp');
Route::post('/login/otp', [CustomerAuthController::class, 'verifyLoginOtp'])->name('frontend.login.otp.verify');
Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('frontend.logout');
Route::get('/register/otp', [CustomerAuthController::class, 'showRegisterOtp'])->name('frontend.register.otp');
Route::post('/register/otp', [CustomerAuthController::class, 'verifyRegisterOtp'])->name('frontend.register.otp.verify');
Route::get('/add-address', [FrontendCheckoutController::class, 'showAddAddress'])->name('frontend.add_address');
Route::post('/add-address', [FrontendCheckoutController::class, 'storeAddress'])->name('frontend.add_address.store');

// Optional compatibility for old template URLs
Route::redirect('/index.html', '/');
Route::redirect('/category.html', '/category');
Route::redirect('/product.html', '/product');
Route::redirect('/cart.html', '/cart');
Route::redirect('/checkout.html', '/checkout');
Route::redirect('/register.html', '/register');
Route::redirect('/login.html', '/login');

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.auth')->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('sub-categories', SubCategoryController::class);
        Route::resource('brands', BrandController::class);
        Route::resource('products', ProductController::class);
        Route::get('product-images', [ProductImageController::class, 'index'])->name('product-images.index');
        Route::get('product-images/create', [ProductImageController::class, 'create'])->name('product-images.create');
        Route::post('product-images', [ProductImageController::class, 'store'])->name('product-images.store');
        Route::get('product-images/{productId}/edit', [ProductImageController::class, 'edit'])->name('product-images.edit');
        Route::put('product-images/{productId}', [ProductImageController::class, 'update'])->name('product-images.update');
        Route::delete('product-images/{productId}', [ProductImageController::class, 'destroy'])->name('product-images.destroy');
        Route::resource('index-banners', IndexBannerController::class);
        Route::resource('offer-details', OfferDetailController::class);
        Route::resource('offer-products', OfferProductController::class);
        Route::resource('uom-masters', UomMasterController::class);
        Route::resource('rate-masters', RateMasterController::class);
        Route::get('stock-infos', [StockInfoController::class, 'index'])->name('stock-infos.index');
        Route::get('stock-infos/create', [StockInfoController::class, 'create'])->name('stock-infos.create');
        Route::post('stock-infos', [StockInfoController::class, 'store'])->name('stock-infos.store');
        Route::get('selected-display', [RateMasterController::class, 'selectedDisplayIndex'])->name('selected-display.index');
        Route::get('selected-display/{productId}', [RateMasterController::class, 'selectedDisplayEdit'])->name('selected-display.edit');
        Route::post('selected-display/{productId}', [RateMasterController::class, 'selectedDisplayUpdate'])->name('selected-display.update');

        Route::resource('users', AdminUserController::class)->except(['destroy']);
        Route::patch('users/{id}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status');
    });
});
