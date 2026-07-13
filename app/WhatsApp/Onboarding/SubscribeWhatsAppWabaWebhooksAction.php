<?php

namespace App\WhatsApp\Onboarding;

use App\Models\Tenant\WhatsAppNumber;
use App\WhatsApp\Services\WhatsAppCloudApiService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SubscribeWhatsAppWabaWebhooksAction
{
    public function __construct(
        protected WhatsAppCloudApiService $cloudApi,
    ) {}

    /**
     * Subscribe the Meta app to the WABA's webhooks (idempotent POST subscribed_apps).
     *
     * @return array{success: bool, http_status: int, success_flag: bool|null, message: string|null}
     */
    public function execute(WhatsAppNumber $number): array
    {
        $wabaId = $number->whatsapp_business_account_id;

        if (blank($wabaId) || blank($number->access_token)) {
            throw new RuntimeException('WABA id and access token are required to subscribe webhooks.');
        }

        $response = $this->cloudApi->subscribeWabaApps($number);

        $safe = [
            'success' => $response->successful(),
            'http_status' => $response->status(),
            'success_flag' => $response->json('success'),
            'message' => $response->successful()
                ? null
                : $this->cloudApi->safeErrorMessage($response),
        ];

        Log::channel(config('whatsapp.log_channel'))->info('WhatsApp WABA subscribed_apps attempted', [
            'waba_id' => $wabaId,
            'phone_number_id' => $number->phone_number_id,
            'http_status' => $safe['http_status'],
            'success' => $safe['success'],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($safe['message'] ?: 'WABA webhook subscription failed.');
        }

        $number->forceFill([
            'webhook_status' => 'subscribed',
        ])->save();

        return $safe;
    }

    /**
     * Token-only variant used before a tenant number row exists.
     *
     * @return array{success: bool, http_status: int, success_flag: bool|null, message: string|null}
     */
    public function executeWithToken(string $accessToken, string $wabaId): array
    {
        if (blank($accessToken) || blank($wabaId)) {
            throw new RuntimeException('WABA id and access token are required to subscribe webhooks.');
        }

        $response = $this->cloudApi->subscribeWabaAppsWithToken($accessToken, $wabaId);

        $safe = [
            'success' => $response->successful(),
            'http_status' => $response->status(),
            'success_flag' => $response->json('success'),
            'message' => $response->successful()
                ? null
                : $this->cloudApi->safeErrorMessage($response),
        ];

        Log::channel(config('whatsapp.log_channel'))->info('WhatsApp WABA subscribed_apps attempted (token path)', [
            'waba_id' => $wabaId,
            'http_status' => $safe['http_status'],
            'success' => $safe['success'],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($safe['message'] ?: 'WABA webhook subscription failed.');
        }

        return $safe;
    }
}
