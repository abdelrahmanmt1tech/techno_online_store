<?php

namespace App\Messenger\Actions;

use App\Models\Tenant\MessengerContact;
use Carbon\CarbonInterface;

class UpsertMessengerContactAction
{
    public function execute(string $psid, ?string $profileName = null, ?CarbonInterface $lastMessageAt = null): MessengerContact
    {
        return MessengerContact::query()->updateOrCreate(
            ['psid' => $psid],
            array_filter([
                'profile_name' => $profileName,
                'last_message_at' => $lastMessageAt,
            ], fn ($value) => $value !== null),
        );
    }
}
