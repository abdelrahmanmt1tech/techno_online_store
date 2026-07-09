<?php

namespace App\WhatsApp\DTOs;

use App\Models\Tenant\WhatsAppConversation;
use App\Models\Tenant\WhatsAppNumber;

class SendTextMessageData
{
    public function __construct(
        public WhatsAppNumber $whatsappNumber,
        public WhatsAppConversation $conversation,
        public string $body,
        public ?int $senderUserId = null,
    ) {}
}
