<?php

namespace App\Enums\Crm;

enum ClientStage: string
{
    case LEAD = 'lead';
    case CUSTOMER = 'customer';
    case ADVANCED = 'advanced';
    case VIP = 'vip';

    public function label(): string
    {
        return __('crm.client_stages.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::LEAD => 'info',
            self::CUSTOMER => 'success',
            self::ADVANCED => 'warning',
            self::VIP => 'danger',
        };
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
