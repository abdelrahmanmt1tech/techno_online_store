<?php

namespace App\WhatsApp\Enums;

enum WhatsAppConnectionStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
    case ReconnectRequired = 'reconnect_required';
    case Failed = 'failed';
}
