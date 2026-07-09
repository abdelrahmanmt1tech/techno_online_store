<?php

namespace App\WhatsApp\Services;

use App\Models\Tenant;
use App\Models\WhatsAppNumberRegistry;

class WhatsAppWebhookResolver
{
    public function resolveByPhoneNumberId(?string $phoneNumberId): ?WhatsAppNumberRegistry
    {
        if (blank($phoneNumberId)) {
            return null;
        }

        return WhatsAppNumberRegistry::query()
            ->where('phone_number_id', $phoneNumberId)
            ->first();
    }

    public function resolveTenant(?string $phoneNumberId): ?Tenant
    {
        $registry = $this->resolveByPhoneNumberId($phoneNumberId);

        if ($registry === null) {
            return null;
        }

        return Tenant::query()->find($registry->tenant_id);
    }
}
