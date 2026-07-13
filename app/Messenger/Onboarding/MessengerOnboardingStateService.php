<?php

namespace App\Messenger\Onboarding;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class MessengerOnboardingStateService
{
    public function issue(
        string $tenantId,
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

        $ttl = $ttlSeconds ?? (int) config('messenger.facebook_login.state_ttl_seconds', 900);
        $now = time();

        $state = new MessengerOnboardingState(
            tenantId: $tenantId,
            nonce: (string) Str::uuid(),
            issuedAt: $now,
            expiresAt: $now + max(60, $ttl),
            returnUrl: $returnUrl,
            userId: $userId,
        );

        return Crypt::encryptString(json_encode($state->toArray(), JSON_THROW_ON_ERROR));
    }

    public function parse(string $token): MessengerOnboardingState
    {
        if (blank($token)) {
            throw new InvalidMessengerOnboardingStateException('Onboarding state is missing.');
        }

        try {
            $decoded = Crypt::decryptString($token);
            $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|RuntimeException|\JsonException) {
            throw new InvalidMessengerOnboardingStateException('Onboarding state is invalid or tampered.');
        }

        if (! is_array($payload)) {
            throw new InvalidMessengerOnboardingStateException('Onboarding state payload is invalid.');
        }

        foreach (['tenant_id', 'nonce', 'issued_at', 'expires_at', 'return_url'] as $key) {
            if (! array_key_exists($key, $payload) || blank($payload[$key])) {
                throw new InvalidMessengerOnboardingStateException("Onboarding state is missing {$key}.");
            }
        }

        $state = MessengerOnboardingState::fromArray($payload);

        if ($state->isExpired()) {
            throw new InvalidMessengerOnboardingStateException('Onboarding state has expired.');
        }

        return $state;
    }

    public function centralUrl(string $path, string $stateToken): string
    {
        $domain = (string) config('messenger.facebook_login.central_domain');
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $path = trim($path, '/');

        return "{$scheme}://{$domain}/messenger/onboarding/{$path}?state=".urlencode($stateToken);
    }

    public function isAllowedCentralHost(string $host): bool
    {
        $expected = strtolower((string) config('messenger.facebook_login.central_domain'));
        $normalized = strtolower($host);

        if ($normalized === $expected) {
            return true;
        }

        return str_starts_with($normalized, $expected.':');
    }

    public function isConfigured(): bool
    {
        return filled(config('messenger.meta_app_id'))
            && filled(config('messenger.app_secret'))
            && filled(config('messenger.facebook_login.config_id'))
            && filled($this->redirectUri());
    }

    public function redirectUri(): string
    {
        $configured = config('messenger.facebook_login.redirect_uri');

        if (filled($configured)) {
            return (string) $configured;
        }

        $domain = (string) config('messenger.facebook_login.central_domain');
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

        return "{$scheme}://{$domain}/messenger/onboarding/callback";
    }

    public function facebookOAuthUrl(string $stateToken): string
    {
        $version = config('messenger.graph_api_version', 'v21.0');
        $query = http_build_query([
            'client_id' => config('messenger.meta_app_id'),
            'redirect_uri' => $this->redirectUri(),
            'state' => $stateToken,
            'response_type' => 'code',
            'config_id' => config('messenger.facebook_login.config_id'),
        ]);

        return "https://www.facebook.com/{$version}/dialog/oauth?{$query}";
    }
}
