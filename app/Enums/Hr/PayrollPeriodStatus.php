<?php

namespace App\Enums\Hr;

enum PayrollPeriodStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Approved = 'approved';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('hr.payroll_period_statuses.'.$this->value);
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::Generated, self::Cancelled], true),
            self::Generated => in_array($next, [self::Approved, self::Cancelled, self::Draft], true),
            self::Approved => in_array($next, [self::Paid], true),
            self::Paid, self::Cancelled => false,
        };
    }
}
