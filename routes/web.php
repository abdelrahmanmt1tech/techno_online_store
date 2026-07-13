<?php

use App\Http\Controllers\MessengerWebhookController;
use App\Http\Controllers\WhatsAppOnboardingController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\EnsureWhatsAppOnboardingCentralDomain;
use Illuminate\Support\Facades\Route;

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
    });
