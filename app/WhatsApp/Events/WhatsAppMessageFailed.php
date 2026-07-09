<?php

namespace App\WhatsApp\Events;

use App\Models\Tenant\WhatsAppMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppMessageFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WhatsAppMessage $message,
    ) {}
}
