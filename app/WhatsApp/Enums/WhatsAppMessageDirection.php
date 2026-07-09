<?php

namespace App\WhatsApp\Enums;

enum WhatsAppMessageDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
}
