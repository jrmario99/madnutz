<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\KitController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Api\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\Admin\UploadController;
use App\Http\Controllers\Customer\CustomerAuthController;
use App\Http\Controllers\Customer\CustomerOrderController;
use App\Http\Controllers\Customer\CustomerFavoriteController;
use App\Http\Controllers\Customer\CustomerProfileController;
use App\Http\Controllers\Customer\CustomerAddressController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CustomerAuth;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::get('settings', [SettingController::class, 'index']);
Route::post('coupons/validate', [CouponController::class, 'validate']);
Route::get('kits', [KitController::class, 'index']);
Route::get('kits/{slug}/products', [KitController::class, 'products']);
Route::get('kits/{slug}', [KitController::class, 'show']);
Route::get('products', [ProductController::class, 'index']);
Route::post('orders', [OrderController::class, 'store']);

// Webhooks de pagamento (sem CSRF/auth)
Route::post('webhooks/b4you', [WebhookController::class, 'b4you']);

// Auth
Route::middleware('throttle:5,1')->post('auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
});

// ── Área do Cliente ──────────────────────────────────────────────────────────
// Público: registro e login
Route::prefix('customer/auth')->middleware('throttle:5,1')->group(function () {
    Route::post('register',        [CustomerAuthController::class, 'register']);
    Route::post('login-password',  [CustomerAuthController::class, 'loginPassword']);
    Route::post('send-otp',        [CustomerAuthController::class, 'sendOtp']);
    Route::post('verify-otp',      [CustomerAuthController::class, 'verifyOtp']);
});

// Protegido: requer token de cliente
Route::prefix('customer')->middleware(CustomerAuth::class)->group(function () {
    Route::get('auth/me',    [CustomerAuthController::class, 'me']);
    Route::post('auth/logout', [CustomerAuthController::class, 'logout']);

    Route::get('orders',             [CustomerOrderController::class, 'index']);
    Route::get('orders/{id}',        [CustomerOrderController::class, 'show']);
    Route::post('orders/{id}/repeat', [CustomerOrderController::class, 'repeat']);

    Route::get('favorites',        [CustomerFavoriteController::class, 'index']);
    Route::post('favorites/toggle', [CustomerFavoriteController::class, 'toggle']);

    Route::put('profile',          [CustomerProfileController::class, 'update']);
    Route::put('profile/password', [CustomerProfileController::class, 'updatePassword']);

    Route::get('addresses',                    [CustomerAddressController::class, 'index']);
    Route::post('addresses',                   [CustomerAddressController::class, 'store']);
    Route::put('addresses/{id}',               [CustomerAddressController::class, 'update']);
    Route::delete('addresses/{id}',            [CustomerAddressController::class, 'destroy']);
    Route::post('addresses/{id}/set-default',  [CustomerAddressController::class, 'setDefault']);
});

// Admin (requer autenticação + is_admin)
Route::prefix('admin')->middleware(['auth:sanctum', AdminMiddleware::class])->group(function () {
    Route::get('products', [Admin\ProductController::class, 'index']);
    Route::post('products', [Admin\ProductController::class, 'store']);
    Route::get('products/{id}', [Admin\ProductController::class, 'show']);
    Route::put('products/{id}', [Admin\ProductController::class, 'update']);
    Route::delete('products/{id}', [Admin\ProductController::class, 'destroy']);
    Route::post('products/{id}/restore', [Admin\ProductController::class, 'restore']);

    Route::get('kits', [Admin\KitController::class, 'index']);
    Route::post('kits', [Admin\KitController::class, 'store']);
    Route::get('kits/{id}', [Admin\KitController::class, 'show']);
    Route::put('kits/{id}', [Admin\KitController::class, 'update']);
    Route::delete('kits/{id}', [Admin\KitController::class, 'destroy']);
    Route::post('kits/reorder', [Admin\KitController::class, 'reorder']);

    Route::post('upload', [UploadController::class, 'store']);
    Route::delete('upload', [UploadController::class, 'destroy']);

    Route::get('orders', [Admin\OrderController::class, 'index']);
    Route::get('orders/stats', [Admin\OrderController::class, 'stats']);
    Route::get('orders/{id}', [Admin\OrderController::class, 'show']);
    Route::put('orders/{id}', [Admin\OrderController::class, 'update']);
    Route::delete('orders/{id}', [Admin\OrderController::class, 'destroy']);

    Route::get('coupons', [AdminCouponController::class, 'index']);
    Route::post('coupons', [AdminCouponController::class, 'store']);
    Route::get('coupons/{id}', [AdminCouponController::class, 'show']);
    Route::put('coupons/{id}', [AdminCouponController::class, 'update']);
    Route::delete('coupons/{id}', [AdminCouponController::class, 'destroy']);

    Route::get('settings', [AdminSettingController::class, 'index']);
    Route::put('settings', [AdminSettingController::class, 'update']);
});
