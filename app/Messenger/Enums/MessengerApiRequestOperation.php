<?php

namespace App\Messenger\Enums;

enum MessengerApiRequestOperation: string
{
    case SendText = 'send_text';

    public function label(): string
    {
        return match ($this) {
            self::SendText => __('dashboard.messenger_api_op_send_text'),
        };
    }
}
