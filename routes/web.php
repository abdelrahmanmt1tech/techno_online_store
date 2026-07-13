<?php

use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\MessengerOnboardingController;
use App\Http\Controllers\MessengerWebhookController;
use App\Http\Controllers\WhatsAppOnboardingController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\EnsureMessengerOnboardingCentralDomain;
use App\Http\Middleware\EnsureWhatsAppOnboardingCentralDomain;
use Illuminate\Support\Facades\Route;

/*
| Public legal pages (central domain — no tenant middleware / no auth).
| Required for Meta App Dashboard: Privacy Policy, Terms, Data Deletion.
*/
Route::get('/privacy-policy', [LegalPageController::class, 'privacyPolicy'])->name('legal.privacy');
Route::get('/terms-of-service', [LegalPageController::class, 'termsOfService'])->name('legal.terms');
Route::get('/data-deletion', [LegalPageController::class, 'dataDeletion'])->name('legal.data-deletion');

Route::get('/webhooks/meta/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhooks/meta/whatsapp', [WhatsAppWebhookController::class, 'receive']);

Route::get('/webhooks/meta/messenger', [MessengerWebhookController::class, 'verify']);
Route::post('/webhooks/meta/messenger', [MessengerWebhookController::class, 'receive']);

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
