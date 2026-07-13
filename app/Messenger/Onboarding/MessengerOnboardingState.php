<?php

namespace App\Messenger\Onboarding;

class MessengerOnboardingState
{
    public function __construct(
        public string $tenantId,
        public string $nonce,
        public int $issuedAt,
        public int $expiresAt,
        public string $returnUrl,
        public int|string|null $userId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
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
        return new self(
            tenantId: (string) $payload['tenant_id'],
            nonce: (string) $payload['nonce'],
            issuedAt: (int) $payload['issued_at'],
            expiresAt: (int) $payload['expires_at'],
            returnUrl: (string) $payload['return_url'],
            userId: $payload['user_id'] ?? null,
        );
    }

    public function isExpired(): bool
    {
        return time() >= $this->expiresAt;
    }
}
