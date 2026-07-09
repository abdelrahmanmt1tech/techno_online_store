<?php

namespace App\WhatsApp\Enums;

enum WhatsAppWebhookProcessingStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Failed = 'failed';
    case Unresolved = 'unresolved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('dashboard.whatsapp_webhook_status_pending'),
            self::Processed => __('dashboard.whatsapp_webhook_status_processed'),
            self::Failed => __('dashboard.whatsapp_webhook_status_failed'),
            self::Unresolved => __('dashboard.whatsapp_webhook_status_unresolved'),
            self::Rejected => __('dashboard.whatsapp_webhook_status_rejected'),
        };
    }
}
