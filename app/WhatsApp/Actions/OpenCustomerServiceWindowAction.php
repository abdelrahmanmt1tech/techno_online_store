<?php

namespace App\WhatsApp\Actions;

use App\Models\Tenant\WhatsAppConversation;
use Carbon\CarbonInterface;

class OpenCustomerServiceWindowAction
{
    public function execute(WhatsAppConversation $conversation, CarbonInterface $customerMessageAt): WhatsAppConversation
    {
        $hours = (int) config('whatsapp.customer_service_window_hours', 24);

        $conversation->update([
            'last_customer_message_at' => $customerMessageAt,
            'customer_service_window_expires_at' => $customerMessageAt->copy()->addHours($hours),
        ]);

        return $conversation->fresh();
    }
}
