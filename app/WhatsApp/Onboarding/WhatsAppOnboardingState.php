<?php

namespace App\WhatsApp\Onboarding;

use App\WhatsApp\Enums\WhatsAppConnectionMethod;

readonly class WhatsAppOnboardingState
{
    public function __construct(
        public string $tenantId,
        public WhatsAppConnectionMethod $connectionMethod,
        public string $nonce,
        public int $issuedAt,
        public int $expiresAt,
        public string $returnUrl,
        public int|string|null $userId = null,
    ) {}

    public function isExpired(?int $now = null): bool
    {
        return ($now ?? time()) >= $this->expiresAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'connection_method' => $this->connectionMethod->value,
            'nonce' => $this->nonce,
            'issued_at' => $this->issuedAt,
            'expires_at' => $this->expiresAt,
            'return_url' => $this->returnUrl,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $method = WhatsAppConnectionMethod::from((string) $payload['connection_method']);

        return new self(
            tenantId: (string) $payload['tenant_id'],
            connectionMethod: $method,
            nonce: (string) $payload['nonce'],
            issuedAt: (int) $payload['issued_at'],
            expiresAt: (int) $payload['expires_at'],
            returnUrl: (string) $payload['return_url'],
            userId: $payload['user_id'] ?? null,
        );
    }
}
