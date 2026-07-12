<?php

namespace App\Messenger\Actions;

use App\Models\Tenant\MessengerConversation;
use Carbon\CarbonInterface;

class OpenMessengerServiceWindowAction
{
    public function execute(MessengerConversation $conversation, CarbonInterface $customerMessageAt): MessengerConversation
    {
        $hours = (int) config('messenger.customer_service_window_hours', 24);

        $conversation->update([
            'last_customer_message_at' => $customerMessageAt,
            'customer_service_window_expires_at' => $customerMessageAt->copy()->addHours($hours),
        ]);

        return $conversation->fresh();
    }
}
