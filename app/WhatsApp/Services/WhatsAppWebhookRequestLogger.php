<?php

namespace App\WhatsApp\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookRequestLogger
{
    public function logVerificationAttempt(
        Request $request,
        ?string $mode,
        ?string $receivedToken,
        ?string $challenge,
        bool $accepted,
        int $statusCode,
    ): void {
        $configuredToken = trim((string) config('whatsapp.webhook_verify_token'));

        $this->info('WhatsApp webhook verification', [
            'direction' => 'inbound',
            'type' => 'verification',
            'method' => $request->method(),
            'path' => $request->path(),
            'host' => $request->getHost(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'query_keys' => array_keys($request->query()),
            'hub_params' => $this->extractHubParams($request),
            'mode' => $mode,
            'mode_is_subscribe' => $mode === 'subscribe',
            'challenge_length' => $challenge !== null ? strlen($challenge) : 0,
            'received_verify_token_masked' => $this->maskSecret($receivedToken),
            'received_verify_token_length' => strlen(trim((string) $receivedToken)),
            'configured_verify_token_set' => $configuredToken !== '',
            'configured_verify_token_length' => strlen($configuredToken),
            'verify_token_matches' => $this->tokensMatch($configuredToken, $receivedToken),
            'accepted' => $accepted,
            'response_status' => $statusCode,
            'response_body_preview' => $accepted ? $challenge : 'Forbidden',
        ]);
    }

    public function logReceiveAttempt(
        Request $request,
        bool $accepted,
        int $statusCode,
        string $reason,
        ?bool $signatureValid = null,
    ): void {
        $this->info('WhatsApp webhook receive', [
            'direction' => 'inbound',
            'type' => 'receive',
            'method' => $request->method(),
            'path' => $request->path(),
            'host' => $request->getHost(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'content_length' => strlen($request->getContent()),
            'has_signature_header' => filled($request->header('X-Hub-Signature-256')),
            'signature_header_preview' => $this->maskSecret($request->header('X-Hub-Signature-256')),
            'meta_app_secret_configured' => filled(config('whatsapp.app_secret')),
            'allow_unsigned_webhooks' => (bool) config('whatsapp.allow_unsigned_webhooks', false),
            'signature_valid' => $signatureValid,
            'accepted' => $accepted,
            'response_status' => $statusCode,
            'response_body_preview' => $accepted ? 'OK' : 'Forbidden',
            'reason' => $reason,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractHubParams(Request $request): array
    {
        $params = [];

        foreach ($request->query() as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (str_starts_with($key, 'hub') || str_contains($key, 'hub_') || str_contains($key, 'hub.')) {
                $params[$key] = is_scalar($value) ? $this->maskHubValue($key, (string) $value) : '[non-scalar]';
            }
        }

        return $params;
    }

    protected function maskHubValue(string $key, string $value): string
    {
        if (str_contains($key, 'verify_token')) {
            return $this->maskSecret($value) ?? '';
        }

        return $value;
    }

    protected function maskSecret(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 2).str_repeat('*', $length - 4).substr($value, -2);
    }

    protected function tokensMatch(string $configuredToken, ?string $receivedToken): bool
    {
        if ($configuredToken === '') {
            return false;
        }

        return hash_equals($configuredToken, trim((string) $receivedToken));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function info(string $message, array $context = []): void
    {
        Log::channel(config('whatsapp.webhook_log_channel'))->info($message, $context);
    }
}
