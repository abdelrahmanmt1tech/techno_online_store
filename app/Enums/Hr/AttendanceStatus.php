<?php

namespace App\Enums\Hr;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Absent = 'absent';
    case Incomplete = 'incomplete';
    case DayOff = 'day_off';
    case Manual = 'manual';

    public function label(): string
    {
        return __('hr.attendance_statuses.'.$this->value);
    }
}
