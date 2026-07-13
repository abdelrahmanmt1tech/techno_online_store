<?php

namespace App\WhatsApp\Services;

use App\Models\Tenant\WhatsAppNumber;
use App\WhatsApp\Enums\WhatsAppApiRequestOperation;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCloudApiService
{
    public function __construct(
        protected WhatsAppApiRequestLogger $apiRequestLogger,
    ) {}

    public function sendText(WhatsAppNumber $number, string $recipientPhone, string $body): Response
    {
        return $this->post($number, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($recipientPhone),
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $body,
            ],
        ], WhatsAppApiRequestOperation::SendText, $recipientPhone);
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     */
    public function sendTemplate(
        WhatsAppNumber $number,
        string $recipientPhone,
        string $templateName,
        string $language,
        array $components = [],
    ): Response {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($recipientPhone),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
            ],
        ];

        if ($components !== []) {
            $payload['template']['components'] = $components;
        }

        return $this->post($number, $payload, WhatsAppApiRequestOperation::SendTemplate, $recipientPhone);
    }

    public function healthCheck(WhatsAppNumber $number): Response
    {
        $version = config('whatsapp.graph_api_version');
        $startedAt = microtime(true);

        $response = Http::timeout(config('whatsapp.request_timeout'))
            ->withToken($number->access_token)
            ->get("https://graph.facebook.com/{$version}/{$number->phone_number_id}");

        $this->apiRequestLogger->log(
            $number,
            WhatsAppApiRequestOperation::HealthCheck,
            ['phone_number_id' => $number->phone_number_id],
            $response,
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
        );

        return $response;
    }

    /**
     * Subscribe this app to WABA webhooks: POST /{waba-id}/subscribed_apps
     */
    public function subscribeWabaApps(WhatsAppNumber $number): Response
    {
        $version = config('whatsapp.graph_api_version');
        $wabaId = $number->whatsapp_business_account_id;
        $startedAt = microtime(true);

        $response = Http::timeout(config('whatsapp.request_timeout'))
            ->withToken($number->access_token)
            ->post("https://graph.facebook.com/{$version}/{$wabaId}/subscribed_apps");

        $this->apiRequestLogger->log(
            $number,
            WhatsAppApiRequestOperation::SubscribeWabaApps,
            ['waba_id' => $wabaId],
            $response,
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
        );

        return $response;
    }

    /**
     * List phone numbers on a WABA: GET /{waba-id}/phone_numbers
     *
     * @param  list<string>  $fields
     */
    public function listWabaPhoneNumbers(
        WhatsAppNumber $number,
        array $fields = ['id', 'display_phone_number', 'verified_name'],
    ): Response {
        $version = config('whatsapp.graph_api_version');
        $wabaId = $number->whatsapp_business_account_id;
        $query = ['fields' => implode(',', $fields)];
        $startedAt = microtime(true);

        $response = Http::timeout(config('whatsapp.request_timeout'))
            ->withToken($number->access_token)
            ->get("https://graph.facebook.com/{$version}/{$wabaId}/phone_numbers", $query);

        $this->apiRequestLogger->log(
            $number,
            WhatsAppApiRequestOperation::ListWabaPhoneNumbers,
            ['waba_id' => $wabaId, 'fields' => $fields],
            $response,
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
        );

        return $response;
    }

    /**
     * Fetch a single phone number node for metadata polish.
     *
     * @param  list<string>  $fields
     */
    public function getPhoneNumber(
        WhatsAppNumber $number,
        ?string $phoneNumberId = null,
        array $fields = ['id', 'display_phone_number', 'verified_name'],
    ): Response {
        $version = config('whatsapp.graph_api_version');
        $phoneId = $phoneNumberId ?: $number->phone_number_id;
        $query = ['fields' => implode(',', $fields)];
        $startedAt = microtime(true);

        $response = Http::timeout(config('whatsapp.request_timeout'))
            ->withToken($number->access_token)
            ->get("https://graph.facebook.com/{$version}/{$phoneId}", $query);

        $this->apiRequestLogger->log(
            $number,
            WhatsAppApiRequestOperation::GetPhoneNumber,
            ['phone_number_id' => $phoneId, 'fields' => $fields],
            $response,
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
        );

        return $response;
    }

    /**
     * Onboarding helper when a tenant WhatsAppNumber row may not exist yet.
     * Does not log to tenant API request table (no number id). Never logs the token.
     */
    public function subscribeWabaAppsWithToken(string $accessToken, string $wabaId): Response
    {
        $version = config('whatsapp.graph_api_version');

        return Http::timeout(config('whatsapp.request_timeout'))
            ->withToken($accessToken)
            ->post("https://graph.facebook.com/{$version}/{$wabaId}/subscribed_apps");
    }

    /**
     * @param  list<string>  $fields
     */
    public function listWabaPhoneNumbersWithToken(
        string $accessToken,
        string $wabaId,
        array $fields = ['id', 'display_phone_number', 'verified_name'],
    ): Response {
        $version = config('whatsapp.graph_api_version');

        return Http::timeout(config('whatsapp.request_timeout'))
            ->withToken($accessToken)
            ->get("https://graph.facebook.com/{$version}/{$wabaId}/phone_numbers", [
                'fields' => implode(',', $fields),
            ]);
    }

    public function listMessageTemplates(WhatsAppNumber $number, ?string $after = null): Response
    {
        $version = config('whatsapp.graph_api_version');

        $query = ['limit' => 100];

        if (filled($after)) {
            $query['after'] = $after;
        }

        $startedAt = microtime(true);

        $response = Http::timeout(config('whatsapp.request_timeout'))
            ->withToken($number->access_token)
            ->get(
                "https://graph.facebook.com/{$version}/{$number->whatsapp_business_account_id}/message_templates",
                $query,
            );

        $this->apiRequestLogger->log(
            $number,
            WhatsAppApiRequestOperation::ListTemplates,
            ['waba_id' => $number->whatsapp_business_account_id, 'query' => $query],
            $response,
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
        );

        return $response;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchAllMessageTemplates(WhatsAppNumber $number): array
    {
        $templates = [];
        $after = null;

        do {
            $response = $this->listMessageTemplates($number, $after);

            if (! $response->successful()) {
                throw new \RuntimeException($this->safeErrorMessage($response));
            }

            $batch = $response->json('data', []);

            if (is_array($batch)) {
                $templates = array_merge($templates, $batch);
            }

            $after = $response->json('paging.cursors.after');
        } while (filled($after));

        return $templates;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function post(
        WhatsAppNumber $number,
        array $payload,
        WhatsAppApiRequestOperation $operation,
        ?string $recipientPhone = null,
    ): Response {
        $version = config('whatsapp.graph_api_version');
        $startedAt = microtime(true);

        $response = Http::timeout(config('whatsapp.request_timeout'))
            ->withToken($number->access_token)
            ->post("https://graph.facebook.com/{$version}/{$number->phone_number_id}/messages", $payload);

        $this->apiRequestLogger->log(
            $number,
            $operation,
            $payload,
            $response,
            $recipientPhone,
            (int) round((microtime(true) - $startedAt) * 1000),
        );

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

    public function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? $phone;
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
        $message = $response->json('error.message', 'WhatsApp API request failed.');

        Log::channel(config('whatsapp.log_channel'))->warning('WhatsApp API error', [
            'status' => $response->status(),
            'code' => $response->json('error.code'),
        ]);

        return is_string($message) ? $message : 'WhatsApp API request failed.';
    }
}
