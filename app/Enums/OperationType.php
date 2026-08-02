<?php

namespace App\Enums;

enum OperationType: string
{
    case OPENING = 'opening';
    case NORMAL = 'normal';
    case ADJUSTMENT = 'adjustment';
    case CLOSING_REVENUE = 'closing_revenue';
    case CLOSING_EXPENSE = 'closing_expense';
    case CLOSING_PROFIT_LOSS = 'closing_profit_loss';
    case CARRY_FORWARD = 'carry_forward';
    case REVERSAL = 'reversal';

    public function label(): string
    {
        return __('dashboard.financial_periods.operation_types.' . $this->value);
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
