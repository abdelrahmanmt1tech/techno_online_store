<?php

namespace App\WhatsApp\Onboarding;

use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class WhatsAppOnboardingStateService
{
    public function issue(
        string $tenantId,
        WhatsAppConnectionMethod $connectionMethod,
        string $returnUrl,
        int|string|null $userId = null,
        ?int $ttlSeconds = null,
    ): string {
        if (blank($tenantId)) {
            throw new InvalidArgumentException('tenant_id is required to issue onboarding state.');
        }

        if (! filter_var($returnUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('return_url must be a valid absolute URL.');
        }

        $ttl = $ttlSeconds ?? (int) config('whatsapp.embedded_signup.state_ttl_seconds', 900);
        $now = time();

        $state = new WhatsAppOnboardingState(
            tenantId: $tenantId,
            connectionMethod: $connectionMethod,
            nonce: (string) Str::uuid(),
            issuedAt: $now,
            expiresAt: $now + max(60, $ttl),
            returnUrl: $returnUrl,
            userId: $userId,
        );

        return Crypt::encryptString(json_encode($state->toArray(), JSON_THROW_ON_ERROR));
    }

    public function parse(string $token): WhatsAppOnboardingState
    {
        if (blank($token)) {
            throw new InvalidWhatsAppOnboardingStateException('Onboarding state is missing.');
        }

        try {
            $decoded = Crypt::decryptString($token);
            $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|RuntimeException|\JsonException) {
            throw new InvalidWhatsAppOnboardingStateException('Onboarding state is invalid or tampered.');
        }

        if (! is_array($payload)) {
            throw new InvalidWhatsAppOnboardingStateException('Onboarding state payload is invalid.');
        }

        foreach (['tenant_id', 'connection_method', 'nonce', 'issued_at', 'expires_at', 'return_url'] as $key) {
            if (! array_key_exists($key, $payload) || blank($payload[$key])) {
                throw new InvalidWhatsAppOnboardingStateException("Onboarding state is missing {$key}.");
            }
        }

        try {
            $state = WhatsAppOnboardingState::fromArray($payload);
        } catch (\Throwable) {
            throw new InvalidWhatsAppOnboardingStateException('Onboarding state contains an invalid connection method.');
        }

        if ($state->isExpired()) {
            throw new InvalidWhatsAppOnboardingStateException('Onboarding state has expired.');
        }

        return $state;
    }

    public function centralUrl(string $path, string $stateToken): string
    {
        $domain = (string) config('whatsapp.embedded_signup.central_domain');
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $path = trim($path, '/');

        return "{$scheme}://{$domain}/whatsapp/onboarding/{$path}?state=".urlencode($stateToken);
    }

    public function isAllowedCentralHost(string $host): bool
    {
        $expected = strtolower((string) config('whatsapp.embedded_signup.central_domain'));
        $normalized = strtolower($host);

        if ($normalized === $expected) {
            return true;
        }

        // Allow host:port when config omits the port (e.g. localhost vs localhost:8000).
        return str_starts_with($normalized, $expected.':');
    }
}
