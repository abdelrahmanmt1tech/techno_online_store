<?php

namespace App\WhatsApp\Enums;

enum WhatsAppMessageSenderType: string
{
    case Customer = 'customer';
    case Agent = 'agent';
    case System = 'system';
}
