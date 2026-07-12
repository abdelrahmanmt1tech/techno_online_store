<?php

namespace App\Messenger\Enums;

enum MessengerWebhookProcessingStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Failed = 'failed';
    case Unresolved = 'unresolved';
    case Rejected = 'rejected';
}
