<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrAttendanceSchedule extends Model
{
    use BelongsToTenantConnection;

    protected $table = 'hr_attendance_schedules';

    protected $fillable = [
        'name',
        'is_default',
        'is_active',
        'late_grace_minutes',
        'early_check_in_minutes',
        'allow_check_out_outside_location',
        'absence_deduction_enabled',
        'late_deduction_enabled',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'allow_check_out_outside_location' => 'boolean',
        'absence_deduction_enabled' => 'boolean',
        'late_deduction_enabled' => 'boolean',
    ];

    public function days(): HasMany
    {
        return $this->hasMany(HrAttendanceScheduleDay::class, 'attendance_schedule_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(HrEmployee::class, 'attendance_schedule_id');
    }
}
