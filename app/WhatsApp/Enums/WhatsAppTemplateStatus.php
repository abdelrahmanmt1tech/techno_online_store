<?php

namespace App\WhatsApp\Enums;

enum WhatsAppTemplateStatus: string
{
    case Approved = 'approved';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Paused = 'paused';
    case Disabled = 'disabled';
    case Unknown = 'unknown';
}
