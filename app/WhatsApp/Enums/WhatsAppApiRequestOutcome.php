<?php

namespace App\WhatsApp\Enums;

enum WhatsAppApiRequestOutcome: string
{
    case Success = 'success';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Success => __('dashboard.whatsapp_api_outcome_success'),
            self::Failed => __('dashboard.whatsapp_api_outcome_failed'),
        };
    }
}
