<?php

namespace App\Enums\Hr;

enum AbsenceDeductionType: string
{
    case None = 'none';
    case DailyRate = 'daily_rate';
    case FixedAmount = 'fixed_amount';

    public function label(): string
    {
        return __('hr.absence_deduction_types.'.$this->value);
    }
}
