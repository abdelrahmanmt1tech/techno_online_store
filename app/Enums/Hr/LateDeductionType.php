<?php

namespace App\Enums\Hr;

enum LateDeductionType: string
{
    case None = 'none';
    case FixedPerLateDay = 'fixed_per_late_day';
    case PerMinute = 'per_minute';

    public function label(): string
    {
        return __('hr.late_deduction_types.'.$this->value);
    }
}
