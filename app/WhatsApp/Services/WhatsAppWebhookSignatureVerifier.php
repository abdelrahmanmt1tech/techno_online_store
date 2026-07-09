<?php

namespace App\WhatsApp\Services;

class WhatsAppWebhookSignatureVerifier
{
    public function verify(string $payload, ?string $signatureHeader): bool
    {
        $secret = config('whatsapp.app_secret');

        if (blank($secret)) {
            return config('whatsapp.allow_unsigned_webhooks', false);
        }

        if (blank($signatureHeader)) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signatureHeader);
    }
}
