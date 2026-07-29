<?php

namespace App\Enums\Hr;

enum AttendanceSource: string
{
    case Employee = 'employee';
    case Admin = 'admin';
    case System = 'system';

    public function label(): string
    {
        return __('hr.attendance_sources.'.$this->value);
    }
}
