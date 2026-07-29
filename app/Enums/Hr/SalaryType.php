<?php

namespace App\Enums\Hr;

enum SalaryType: string
{
    case Monthly = 'monthly';
    case Daily = 'daily';

    public function label(): string
    {
        return __('hr.salary_types.'.$this->value);
    }
}
