<?php

namespace App\Enums\Hr;

enum EmploymentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Terminated = 'terminated';

    public function label(): string
    {
        return __('hr.employment_statuses.'.$this->value);
    }
}
