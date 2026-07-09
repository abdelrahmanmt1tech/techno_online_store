<?php

use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/webhooks/meta/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhooks/meta/whatsapp', [WhatsAppWebhookController::class, 'receive']);
