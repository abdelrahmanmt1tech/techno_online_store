<?php

namespace App\Enums;

enum FinancialPeriodStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case CLOSING = 'closing';
    case CLOSED = 'closed';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return __('dashboard.financial_periods.statuses.' . $this->value);
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
