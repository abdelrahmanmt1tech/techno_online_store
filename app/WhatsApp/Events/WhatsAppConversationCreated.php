<?php

namespace App\WhatsApp\Events;

use App\Models\Tenant\WhatsAppConversation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppConversationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WhatsAppConversation $conversation,
    ) {}
}
