<?php

namespace App\Messenger\Onboarding;

use App\Messenger\Services\MessengerGraphApiService;
use App\Models\Tenant\MessengerPage;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SubscribeMessengerPageWebhooksAction
{
    public function __construct(
        protected MessengerGraphApiService $graphApi,
    ) {}

    /**
     * @return array{success: bool, http_status: int, success_flag: bool|null, message: string|null}
     */
    public function execute(MessengerPage $page): array
    {
        if (blank($page->page_id) || blank($page->page_access_token)) {
            throw new RuntimeException('Page id and access token are required to subscribe webhooks.');
        }

        $response = $this->graphApi->subscribePageApps($page);

        $safe = [
            'success' => $response->successful(),
            'http_status' => $response->status(),
            'success_flag' => $response->json('success'),
            'message' => $response->successful()
                ? null
                : $this->graphApi->safeErrorMessage($response),
        ];

        Log::channel(config('messenger.log_channel'))->info('Messenger Page subscribed_apps result', [
            'page_id' => $page->page_id,
            'http_status' => $safe['http_status'],
            'success' => $safe['success'],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($safe['message'] ?: 'Page webhook subscription failed.');
        }

        $page->forceFill([
            'webhook_status' => 'subscribed',
        ])->save();

        return $safe;
    }
}
