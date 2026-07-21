<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Tenant\CartController;
use App\Http\Controllers\Api\Tenant\CategoryController;
use App\Http\Controllers\Api\Tenant\CheckoutController;
use App\Http\Controllers\Api\Tenant\CheckoutOtpController;
use App\Http\Controllers\Api\Tenant\ContactController;
use App\Http\Controllers\Api\Tenant\GovernorateController;
use App\Http\Controllers\Api\Tenant\OrderController;
use App\Http\Controllers\Api\Tenant\ProductController;
use App\Http\Controllers\Api\Tenant\Auth\LoginController;
use App\Http\Controllers\Api\Tenant\Auth\PasswordResetController;
use App\Http\Controllers\Api\Tenant\Auth\RegisterController;
use App\Http\Controllers\Auth\Tenant\TenantTokenLoginController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/app/login/{token}', TenantTokenLoginController::class)->name('tenant.token-login');

    // Do not register GET / here — it overwrites the central home landing in routes/web.php
    // and PreventAccessFromCentralDomains then returns 404 on central domains.

    Route::prefix('api')->group(function () {
        // Authentication
        Route::prefix('auth')->group(function () {
            Route::post('register', [RegisterController::class, 'register']);
            Route::post('verify', [RegisterController::class, 'verifyAccount']);
            Route::post('resend-code', [RegisterController::class, 'resendCode']);
            Route::post('login', [LoginController::class, 'login']);
            Route::post('logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');
            Route::post('logout-all', [LoginController::class, 'logoutAll'])->middleware('auth:sanctum');
            Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword']);
            Route::post('verify-reset-code', [PasswordResetController::class, 'verifyResetCode']);
            Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);
        });

        // المنتجات (عام)
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{slug}', [ProductController::class, 'show']);

        // التصنيفات (عام)
        Route::get('tenant/categories', [CategoryController::class, 'index']);

        // المحافظات (عام)
        Route::get('governorates', [GovernorateController::class, 'index']);

        // جهات الاتصال
        Route::post('contacts', [ContactController::class, 'store']);

        // السلة
        Route::post('cart/items', [CartController::class, 'addItem']);
        Route::get('cart/{token}', [CartController::class, 'show']);
        Route::post('cart/{token}/items/{item}', [CartController::class, 'updateItem']);
        Route::delete('cart/{token}/items/{item}', [CartController::class, 'removeItem']);
        Route::post('cart/{token}/governorate', [CartController::class, 'setGovernorate']);

        // الكوبونات
        Route::post('cart/{token}/coupon', [CartController::class, 'applyCoupon']);

        // إتمام الطلب والتتبع
        Route::post('cart/{token}/checkout/send-otp', [CheckoutOtpController::class, 'sendOtp']);
        Route::post('cart/{token}/checkout/verify', [CheckoutOtpController::class, 'verifyAndCheckout']);
        Route::post('checkout/{token}', [CheckoutController::class, 'store']);
        Route::get('orders/{token}', [OrderController::class, 'show']);
    });
});
