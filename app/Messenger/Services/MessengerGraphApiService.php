<?php

namespace App\Messenger\Services;

use App\Messenger\Enums\MessengerApiRequestOperation;
use App\Models\Tenant\MessengerPage;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessengerGraphApiService
{
    public function __construct(
        protected MessengerApiRequestLogger $apiRequestLogger,
    ) {}

    public function sendText(MessengerPage $page, string $recipientPsid, string $body): Response
    {
        $version = config('messenger.graph_api_version');
        $timeout = (int) config('messenger.request_timeout', 30);
        $payload = [
            'recipient' => ['id' => $recipientPsid],
            'messaging_type' => 'RESPONSE',
            'message' => ['text' => $body],
        ];

        $startedAt = microtime(true);

        $response = Http::timeout($timeout)
            ->withToken($page->page_access_token)
            ->post("https://graph.facebook.com/{$version}/{$page->page_id}/messages", $payload);

        $this->apiRequestLogger->log(
            $page,
            MessengerApiRequestOperation::SendText,
            $payload,
            $response,
            $recipientPsid,
            (int) round((microtime(true) - $startedAt) * 1000),
        );

        if ($response->failed()) {
            Log::channel(config('messenger.log_channel'))->warning('Messenger Graph API send failed', [
                'page_id' => $page->page_id,
                'http_status' => $response->status(),
                'error_code' => $response->json('error.code'),
                'error_message' => $response->json('error.message'),
            ]);
        } else {
            Log::channel(config('messenger.log_channel'))->info('Messenger Graph API send ok', [
                'page_id' => $page->page_id,
                'http_status' => $response->status(),
                'message_id' => $response->json('message_id'),
            ]);
        }

        return $response;
    }

    public function getLastLoggedRequestId(): ?int
    {
        return $this->apiRequestLogger->getLastLoggedRequestId();
    }

    public function attachLastLoggedRequestToMessage(int $messageId): void
    {
        $requestId = $this->getLastLoggedRequestId();

        if ($requestId !== null) {
            $this->apiRequestLogger->attachMessage($requestId, $messageId);
        }
    }

    public function isAuthenticationError(Response $response): bool
    {
        if ($response->status() === 401) {
            return true;
        }

        $code = $response->json('error.code');

        return in_array($code, [190, 102], true);
    }

    public function safeErrorMessage(Response $response): string
    {
        $message = $response->json('error.message', 'Messenger API request failed.');

        return is_string($message) ? $message : 'Messenger API request failed.';
    }
}
