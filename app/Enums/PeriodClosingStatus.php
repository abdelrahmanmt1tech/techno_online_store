<?php

namespace App\Enums;

enum PeriodClosingStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REOPENED = 'reopened';

    public function label(): string
    {
        return __('dashboard.financial_periods.closing_statuses.' . $this->value);
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
