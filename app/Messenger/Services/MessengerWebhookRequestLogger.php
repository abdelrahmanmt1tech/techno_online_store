<?php

namespace App\Messenger\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessengerWebhookRequestLogger
{
    public function logVerificationAttempt(
        Request $request,
        ?string $mode,
        ?string $receivedToken,
        ?string $challenge,
        bool $accepted,
        int $statusCode,
    ): void {
        $configuredToken = trim((string) config('messenger.webhook_verify_token'));

        Log::channel(config('messenger.webhook_log_channel'))->info('Messenger webhook verification', [
            'direction' => 'inbound',
            'type' => 'verification',
            'path' => $request->path(),
            'mode' => $mode,
            'challenge_length' => $challenge !== null ? strlen($challenge) : 0,
            'received_verify_token_masked' => $this->maskSecret($receivedToken),
            'configured_verify_token_set' => $configuredToken !== '',
            'verify_token_matches' => hash_equals($configuredToken, trim((string) $receivedToken)),
            'accepted' => $accepted,
            'response_status' => $statusCode,
        ]);
    }

    public function logReceiveAttempt(
        Request $request,
        bool $accepted,
        int $statusCode,
        string $reason,
        ?bool $signatureValid = null,
    ): void {
        Log::channel(config('messenger.webhook_log_channel'))->info('Messenger webhook receive', [
            'direction' => 'inbound',
            'type' => 'receive',
            'path' => $request->path(),
            'content_length' => strlen($request->getContent()),
            'has_signature_header' => filled($request->header('X-Hub-Signature-256')),
            'signature_header_preview' => $this->maskSecret($request->header('X-Hub-Signature-256')),
            'meta_app_secret_configured' => filled(config('messenger.app_secret')),
            'signature_valid' => $signatureValid,
            'accepted' => $accepted,
            'response_status' => $statusCode,
            'reason' => $reason,
        ]);
    }

    protected function maskSecret(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $trimmed = trim($value);

        if (strlen($trimmed) <= 8) {
            return str_repeat('*', strlen($trimmed));
        }

        return substr($trimmed, 0, 4).str_repeat('*', max(0, strlen($trimmed) - 8)).substr($trimmed, -4);
    }
}
