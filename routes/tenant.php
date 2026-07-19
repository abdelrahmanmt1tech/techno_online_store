<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Tenant\CartController;
use App\Http\Controllers\Api\Tenant\CheckoutController;
use App\Http\Controllers\Api\Tenant\GovernorateController;
use App\Http\Controllers\Api\Tenant\OrderController;
use App\Http\Controllers\Api\Tenant\ProductController;
use App\Http\Controllers\Auth\Tenant\TenantTokenLoginController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/app/login/{token}', TenantTokenLoginController::class)->name('tenant.token-login');

    // Do not register GET / here — it overwrites the central home landing in routes/web.php
    // and PreventAccessFromCentralDomains then returns 404 on central domains.

    Route::prefix('api')->group(function () {
        // المنتجات (عام)
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{slug}', [ProductController::class, 'show']);

        // المحافظات (عام)
        Route::get('governorates', [GovernorateController::class, 'index']);

        // السلة
        Route::post('cart', [CartController::class, 'store']);
        Route::get('cart/{token}', [CartController::class, 'show']);
        Route::post('cart/{token}/items', [CartController::class, 'addItem']);
        Route::put('cart/{token}/items/{item}', [CartController::class, 'updateItem']);
        Route::delete('cart/{token}/items/{item}', [CartController::class, 'removeItem']);
        Route::put('cart/{token}/governorate', [CartController::class, 'setGovernorate']);

        // الكوبونات
        Route::post('cart/{token}/coupon', [CartController::class, 'applyCoupon']);
        Route::delete('cart/{token}/coupon', [CartController::class, 'removeCoupon']);

        // إتمام الطلب والتتبع
        Route::post('checkout/{token}', [CheckoutController::class, 'store']);
        Route::get('orders/{token}', [OrderController::class, 'show']);
    });
});
