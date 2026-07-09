<?php

namespace App\WhatsApp\Enums;

enum WhatsAppWebhookProcessingStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Failed = 'failed';
    case Unresolved = 'unresolved';
    case Rejected = 'rejected';
}
