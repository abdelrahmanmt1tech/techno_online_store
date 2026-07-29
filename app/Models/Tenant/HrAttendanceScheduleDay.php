<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrAttendanceScheduleDay extends Model
{
    use BelongsToTenantConnection;

    protected $table = 'hr_attendance_schedule_days';

    protected $fillable = [
        'attendance_schedule_id',
        'day_of_week',
        'is_working_day',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'is_working_day' => 'boolean',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(HrAttendanceSchedule::class, 'attendance_schedule_id');
    }
}
