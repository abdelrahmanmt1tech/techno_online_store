<?php

namespace App\WhatsApp\DTOs;

use App\Models\Tenant\WhatsAppConversation;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\Tenant\WhatsAppTemplate;

class SendTemplateMessageData
{
    /**
     * @param  array<string, string>  $variables
     */
    public function __construct(
        public WhatsAppNumber $whatsappNumber,
        public WhatsAppConversation $conversation,
        public WhatsAppTemplate $template,
        public array $variables = [],
        public ?int $senderUserId = null,
    ) {}
}
