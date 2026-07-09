<?php

namespace App\WhatsApp\Services;

use App\Models\Tenant\WhatsAppNumber;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCloudApiService
{
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
        ]);
    }

    /**
     * @param  array<string, string>  $bodyVariables
     * @param  array<string, string>  $headerVariables
     */
    public function sendTemplate(
        WhatsAppNumber $number,
        string $recipientPhone,
        string $templateName,
        string $language,
        array $bodyVariables = [],
        array $headerVariables = [],
    ): Response {
        $components = [];

        if ($headerVariables !== []) {
            $components[] = [
                'type' => 'header',
                'parameters' => array_map(
                    fn (string $value) => ['type' => 'text', 'text' => $value],
                    array_values($headerVariables),
                ),
            ];
        }

        if ($bodyVariables !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $value) => ['type' => 'text', 'text' => $value],
                    array_values($bodyVariables),
                ),
            ];
        }

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

        return $this->post($number, $payload);
    }

    public function healthCheck(WhatsAppNumber $number): Response
    {
        $version = config('whatsapp.graph_api_version');

        return Http::timeout(config('whatsapp.request_timeout'))
            ->withToken($number->access_token)
            ->get("https://graph.facebook.com/{$version}/{$number->phone_number_id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function post(WhatsAppNumber $number, array $payload): Response
    {
        $version = config('whatsapp.graph_api_version');

        return Http::timeout(config('whatsapp.request_timeout'))
            ->withToken($number->access_token)
            ->post("https://graph.facebook.com/{$version}/{$number->phone_number_id}/messages", $payload);
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
