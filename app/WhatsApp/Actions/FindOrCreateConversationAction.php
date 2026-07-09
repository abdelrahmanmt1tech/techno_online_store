<?php

namespace App\WhatsApp\Actions;

use App\Models\Tenant\WhatsAppConversation;
use App\Models\Tenant\WhatsAppNumber;
use App\WhatsApp\Enums\WhatsAppConversationStatus;
use App\WhatsApp\Events\WhatsAppConversationCreated;

class FindOrCreateConversationAction
{
    public function execute(
        WhatsAppNumber $number,
        string $customerPhone,
        ?string $customerName = null,
    ): WhatsAppConversation {
        $normalizedPhone = preg_replace('/\D+/', '', $customerPhone) ?? $customerPhone;

        $conversation = WhatsAppConversation::query()->firstOrCreate(
            [
                'whatsapp_number_id' => $number->id,
                'customer_phone' => $normalizedPhone,
            ],
            [
                'customer_name' => $customerName,
                'status' => WhatsAppConversationStatus::Open,
            ],
        );

        if ($conversation->wasRecentlyCreated) {
            event(new WhatsAppConversationCreated($conversation));
        } elseif ($customerName && blank($conversation->customer_name)) {
            $conversation->update(['customer_name' => $customerName]);
        }

        return $conversation;
    }
}
