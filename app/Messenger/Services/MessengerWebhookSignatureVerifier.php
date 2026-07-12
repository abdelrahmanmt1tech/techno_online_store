<?php

namespace App\Messenger\Services;

class MessengerWebhookSignatureVerifier
{
    public function verify(string $payload, ?string $signatureHeader): bool
    {
        $secret = config('messenger.app_secret');

        if (blank($secret)) {
            return (bool) config('messenger.allow_unsigned_webhooks', false);
        }

        if (blank($signatureHeader)) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $payload, (string) $secret);

        return hash_equals($expected, $signatureHeader);
    }
}
