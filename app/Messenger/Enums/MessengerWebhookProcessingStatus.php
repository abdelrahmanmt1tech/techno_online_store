<?php

namespace App\Messenger\Enums;

enum MessengerWebhookProcessingStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Failed = 'failed';
    case Unresolved = 'unresolved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('dashboard.messenger_webhook_status_pending'),
            self::Processed => __('dashboard.messenger_webhook_status_processed'),
            self::Failed => __('dashboard.messenger_webhook_status_failed'),
            self::Unresolved => __('dashboard.messenger_webhook_status_unresolved'),
            self::Rejected => __('dashboard.messenger_webhook_status_rejected'),
        };
    }
}
