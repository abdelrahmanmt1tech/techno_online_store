<?php

namespace App\WhatsApp\Enums;

enum WhatsAppConversationStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Closed = 'closed';
}
