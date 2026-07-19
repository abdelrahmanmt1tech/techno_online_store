<?php

use App\Http\Controllers\Auth\Tenant\ForgotPasswordController;
use App\Http\Controllers\Auth\Tenant\TenantLoginController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\MessengerOnboardingController;
use App\Http\Controllers\MessengerWebhookController;
use App\Http\Controllers\PlatformLandingController;
use App\Http\Controllers\WhatsAppOnboardingController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\EnsureMessengerOnboardingCentralDomain;
use App\Http\Middleware\EnsurePublicCentralDomain;
use App\Http\Middleware\EnsureWhatsAppOnboardingCentralDomain;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
| Public legal pages (central domain — no tenant middleware / no auth).
| Required for Meta App Dashboard: Privacy Policy, Terms, Data Deletion.
*/
Route::get('/', function () {
    return view('platform.index', [
        'supportEmail' => config('app.support_email', 'support@technomasr.com'),
        'companyUrl' => 'https://technomasr.com',
        'contactUrl' => 'https://technomasr.com/en/contact/product-1773666026-noxmd',
        'platformUrl' => 'https://online-store.technomasrsystems.com',
        'canonicalUrl' => 'https://online-store.technomasrsystems.com/platform',
        'privacyUrl' => 'https://online-store.technomasrsystems.com/privacy-policy',
        'termsUrl' => 'https://online-store.technomasrsystems.com/terms-of-service',
        'deletionUrl' => 'https://online-store.technomasrsystems.com/data-deletion',
        'companyProductUrl' => 'https://technomasr.com/techno-online-store.html',
    ]);
})->name('home');

Route::get('/platform', PlatformLandingController::class)
    ->middleware([EnsurePublicCentralDomain::class])
    ->name('platform.landing');

Route::get('/privacy-policy', [LegalPageController::class, 'privacyPolicy'])->name('legal.privacy');
Route::get('/terms-of-service', [LegalPageController::class, 'termsOfService'])->name('legal.terms');
Route::get('/data-deletion', [LegalPageController::class, 'dataDeletion'])->name('legal.data-deletion');

Route::get('/webhooks/meta/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhooks/meta/whatsapp', [WhatsAppWebhookController::class, 'receive']);

Route::get('/webhooks/meta/messenger', [MessengerWebhookController::class, 'verify']);
Route::post('/webhooks/meta/messenger', [MessengerWebhookController::class, 'receive']);

Route::prefix('tenant-login')->group(function () {
    Route::get('/', [TenantLoginController::class, 'showLoginForm'])->name('tenant-login.form');
    Route::post('/', [TenantLoginController::class, 'login'])->name('tenant-login.login');
});

Route::prefix('tenant/forgot-password')->group(function () {
    Route::get('/', [ForgotPasswordController::class, 'showForm'])->name('tenant.forgot-password.form');
    Route::post('/send', [ForgotPasswordController::class, 'sendOtp'])->name('tenant.forgot-password.send');
    Route::get('/verify', [ForgotPasswordController::class, 'showVerifyForm'])->name('tenant.forgot-password.verify-form');
    Route::post('/verify', [ForgotPasswordController::class, 'verifyOtp'])->name('tenant.forgot-password.verify');
    Route::get('/reset', [ForgotPasswordController::class, 'showResetForm'])->name('tenant.forgot-password.reset-form');
    Route::post('/reset', [ForgotPasswordController::class, 'resetPassword'])->name('tenant.forgot-password.reset');
});
Route::prefix('whatsapp/onboarding')
    ->middleware([EnsureWhatsAppOnboardingCentralDomain::class])
    ->group(function () {
        Route::get('start', [WhatsAppOnboardingController::class, 'start'])->name('whatsapp.onboarding.start');
        Route::get('callback', [WhatsAppOnboardingController::class, 'callback'])->name('whatsapp.onboarding.callback');
        Route::get('status', [WhatsAppOnboardingController::class, 'status'])->name('whatsapp.onboarding.status');
        Route::post('complete', [WhatsAppOnboardingController::class, 'complete'])->name('whatsapp.onboarding.complete');
        Route::post('finalize', [WhatsAppOnboardingController::class, 'finalize'])->name('whatsapp.onboarding.finalize');
    });

Route::prefix('messenger/onboarding')
    ->middleware([EnsureMessengerOnboardingCentralDomain::class])
    ->group(function () {
        Route::get('start', [MessengerOnboardingController::class, 'start'])->name('messenger.onboarding.start');
        Route::get('callback', [MessengerOnboardingController::class, 'callback'])->name('messenger.onboarding.callback');
        Route::get('pages', [MessengerOnboardingController::class, 'pages'])->name('messenger.onboarding.pages');
        Route::post('connect', [MessengerOnboardingController::class, 'connect'])->name('messenger.onboarding.connect');
        Route::get('status', [MessengerOnboardingController::class, 'status'])->name('messenger.onboarding.status');
    });




//     Route::get('/smtp-test', function () {
//     Mail::raw('hello', function ($message) {
//         $message->to('mohamed.sala71996@gmail.com')
//                 ->subject('test');
//     });

//     return 'sent';
// });


//  Route::get('/clear-config', function () {
//     Artisan::call('optimize:clear');

//     return nl2br(Artisan::output());
//  });
