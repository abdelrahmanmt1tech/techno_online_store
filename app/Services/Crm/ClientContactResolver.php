<?php

namespace App\Services\Crm;

use App\Models\Tenant\Client;
use App\Models\ContactInfo;
use App\Services\Phone\PhoneNormalizer;
use Illuminate\Database\Eloquent\Builder;

class ClientContactResolver
{
    public function __construct(
        protected PhoneNormalizer $phoneNormalizer,
    ) {}

    public function normalizePhone(?string $raw): ?string
    {
        return $this->phoneNormalizer->normalize($raw);
    }

    public function findClientByPhone(?string $rawPhone, ?int $excludeClientId = null): ?Client
    {
        $normalized = $this->normalizePhone($rawPhone);

        if ($normalized === null) {
            return null;
        }

        return Client::query()
            ->when($excludeClientId, fn (Builder $query) => $query->where('id', '!=', $excludeClientId))
            ->whereHas('contactInfos', fn (Builder $query) => $this->applyNormalizedPhoneConstraints($query, $normalized))
            ->first();
    }

    public function findContactInfoByNormalizedPhone(string $normalizedPhone): ?ContactInfo
    {
        return ContactInfo::query()
            ->where('contactable_type', Client::class)
            ->where(function (Builder $query) use ($normalizedPhone): void {
                $this->applyNormalizedPhoneConstraints($query, $normalizedPhone);
            })
            ->first();
    }

    public function clientHasNormalizedPhone(Client $client, string $normalizedPhone): bool
    {
        return $client->contactInfos()
            ->where(function (Builder $query) use ($normalizedPhone): void {
                $this->applyNormalizedPhoneConstraints($query, $normalizedPhone);
            })
            ->exists();
    }

    protected function applyNormalizedPhoneConstraints(Builder $query, string $normalizedPhone): void
    {
        $query->where(function (Builder $inner) use ($normalizedPhone): void {
            $inner->where('whatsapp_e164', $normalizedPhone)
                ->orWhere('phone_e164', $normalizedPhone)
                ->orWhere('whatsapp', $normalizedPhone)
                ->orWhere('phone', $normalizedPhone);
        });
    }
}
