<?php

namespace App\Enums;

enum PeriodTransferStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REVERSED = 'reversed';

    public function label(): string
    {
        return __('dashboard.financial_periods.transfer_statuses.' . $this->value);
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
