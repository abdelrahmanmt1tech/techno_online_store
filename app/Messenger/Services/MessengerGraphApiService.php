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

    /**
     * List Pages the user manages: GET /me/accounts
     *
     * @return list<array{page_id: string, page_name: ?string, page_access_token: string}>
     */
    public function listManagedPages(string $userAccessToken): array
    {
        $version = config('messenger.graph_api_version');
        $timeout = (int) config('messenger.request_timeout', 30);
        $pages = [];
        $url = "https://graph.facebook.com/{$version}/me/accounts";
        $query = [
            'fields' => 'id,name,access_token',
            'limit' => 100,
        ];

        do {
            $response = Http::timeout($timeout)
                ->withToken($userAccessToken)
                ->get($url, $query);

            if (! $response->successful()) {
                throw new \RuntimeException($this->safeErrorMessage($response));
            }

            $batch = $response->json('data', []);

            if (is_array($batch)) {
                foreach ($batch as $row) {
                    if (! is_array($row) || blank($row['id'] ?? null) || blank($row['access_token'] ?? null)) {
                        continue;
                    }

                    $pages[] = [
                        'page_id' => (string) $row['id'],
                        'page_name' => isset($row['name']) ? (string) $row['name'] : null,
                        'page_access_token' => (string) $row['access_token'],
                    ];
                }
            }

            $next = $response->json('paging.next');
            $url = is_string($next) && $next !== '' ? $next : null;
            $query = [];
        } while (filled($url));

        Log::channel(config('messenger.log_channel'))->info('Messenger managed pages listed', [
            'count' => count($pages),
        ]);

        return $pages;
    }

    /**
     * Subscribe this app to Page webhooks: POST /{page-id}/subscribed_apps
     *
     * @param  list<string>|null  $fields
     */
    public function subscribePageApps(MessengerPage $page, ?array $fields = null): Response
    {
        $version = config('messenger.graph_api_version');
        $timeout = (int) config('messenger.request_timeout', 30);
        $fields ??= config('messenger.facebook_login.subscribed_fields', [
            'messages',
            'messaging_postbacks',
            'message_deliveries',
            'message_reads',
            'messaging_seen',
        ]);

        $payload = [
            'subscribed_fields' => array_values($fields),
        ];

        $startedAt = microtime(true);

        $response = Http::timeout($timeout)
            ->withToken($page->page_access_token)
            ->post("https://graph.facebook.com/{$version}/{$page->page_id}/subscribed_apps", $payload);

        $this->apiRequestLogger->log(
            $page,
            MessengerApiRequestOperation::SubscribePageApps,
            ['page_id' => $page->page_id, 'subscribed_fields' => $fields],
            $response,
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
        );

        Log::channel(config('messenger.log_channel'))->info('Messenger Page subscribed_apps attempted', [
            'page_id' => $page->page_id,
            'http_status' => $response->status(),
            'success' => $response->successful(),
        ]);

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
