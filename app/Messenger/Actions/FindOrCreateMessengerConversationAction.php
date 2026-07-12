<?php

namespace App\Messenger\Actions;

use App\Messenger\Enums\MessengerConversationStatus;
use App\Models\Tenant\MessengerConversation;
use App\Models\Tenant\MessengerPage;

class FindOrCreateMessengerConversationAction
{
    public function execute(
        MessengerPage $page,
        string $senderPsid,
        ?int $contactId = null,
        ?string $customerName = null,
    ): MessengerConversation {
        $conversation = MessengerConversation::query()->firstOrCreate(
            [
                'messenger_page_id' => $page->id,
                'sender_psid' => $senderPsid,
            ],
            [
                'contact_id' => $contactId,
                'customer_name' => $customerName,
                'status' => MessengerConversationStatus::Open,
            ],
        );

        $updates = [];

        if ($contactId !== null && $conversation->contact_id === null) {
            $updates['contact_id'] = $contactId;
        }

        if ($customerName && blank($conversation->customer_name)) {
            $updates['customer_name'] = $customerName;
        }

        if ($updates !== []) {
            $conversation->update($updates);
        }

        return $conversation->fresh();
    }
}
