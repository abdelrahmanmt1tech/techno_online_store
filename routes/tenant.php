<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Tenant\Auth\LoginController;
use App\Http\Controllers\Api\Tenant\Auth\PasswordResetController;
use App\Http\Controllers\Api\Tenant\Auth\RegisterController;
use App\Http\Controllers\Api\Tenant\BranchController;
use App\Http\Controllers\Api\Tenant\CartController;
use App\Http\Controllers\Api\Tenant\CategoryController;
use App\Http\Controllers\Api\Tenant\CheckoutController;
use App\Http\Controllers\Api\Tenant\CheckoutOtpController;
use App\Http\Controllers\Api\Tenant\ContactController;
use App\Http\Controllers\Api\Tenant\FavoriteController;
use App\Http\Controllers\Api\Tenant\FooterController;
use App\Http\Controllers\Api\Tenant\GovernorateController;
use App\Http\Controllers\Api\Tenant\HomeController;
use App\Http\Controllers\Api\Tenant\OrderController;
use App\Http\Controllers\Api\Tenant\PageController;
use App\Http\Controllers\Api\Tenant\ProductController;
use App\Http\Controllers\Api\Tenant\ProfileController;
use App\Http\Controllers\Api\Tenant\ReviewController;
use App\Http\Controllers\Api\Tenant\SettingController;
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

    Route::prefix('api/tenant')->group(function () {
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
        Route::get('products/{slug}/similar', [ProductController::class, 'similar']);

        // التصنيفات (عام)
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('categories/{slug}', [CategoryController::class, 'show']);

        // المحافظات (عام)
        Route::get('governorates', [GovernorateController::class, 'index']);

        // جهات الاتصال
        Route::post('contacts', [ContactController::class, 'store']);

        // المفضلة
        Route::prefix('favorites')->middleware(['auth:sanctum'])->group(function () {
            Route::post('/', [FavoriteController::class, 'toggle']);
            Route::get('/', [FavoriteController::class, 'getFavorites']);
        });

        // الملف الشخصي
        Route::prefix('profile')->middleware(['auth:sanctum'])->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::post('/', [ProfileController::class, 'update']);
            Route::post('/password', [ProfileController::class, 'updatePassword']);
        });

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

        Route::prefix('my-orders')->middleware(['auth:sanctum'])->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::get('{id}', [OrderController::class, 'showById']);
        });

        Route::get('orders/{token}', [OrderController::class, 'show']);

        // المراجعات
        Route::post('reviews', [ReviewController::class, 'store'])->middleware('auth:sanctum');
        Route::get('products/{slug}/reviews', [ReviewController::class, 'index']);

        // الصفحة الرئيسية
        Route::get('home', HomeController::class);

        // الإعدادات
        Route::get('settings', SettingController::class);

        // الفوتر
        Route::get('footer', FooterController::class);

        // اتصل بنا
        Route::get('contact-us/page-data', [ContactController::class, 'contactUs']);

        // الفروع
        Route::get('branches', [BranchController::class, 'index']);
        Route::get('branches/{branch:slug}', [BranchController::class, 'show']);

        // الصفحات
        Route::get('pages', [PageController::class, 'index']);
        Route::get('pages/{slug}', [PageController::class, 'show']);
    });
});
