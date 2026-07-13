<?php

namespace App\Messenger\Enums;

enum MessengerApiRequestOutcome: string
{
    case Success = 'success';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Success => __('dashboard.messenger_api_outcome_success'),
            self::Failed => __('dashboard.messenger_api_outcome_failed'),
        };
    }
}
