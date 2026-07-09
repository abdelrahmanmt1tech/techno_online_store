<?php

namespace App\WhatsApp\Actions;

use App\Models\Tenant\WhatsAppContact;
use Carbon\CarbonInterface;

class UpsertWhatsAppContactAction
{
    public function execute(string $phone, ?string $profileName = null, ?CarbonInterface $lastMessageAt = null): WhatsAppContact
    {
        $normalizedPhone = preg_replace('/\D+/', '', $phone) ?? $phone;

        return WhatsAppContact::query()->updateOrCreate(
            ['phone' => $normalizedPhone],
            array_filter([
                'profile_name' => $profileName,
                'last_message_at' => $lastMessageAt,
            ], fn ($value) => $value !== null),
        );
    }
}
