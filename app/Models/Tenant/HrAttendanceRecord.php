<?php

namespace App\Models\Tenant;

use App\Enums\Hr\AttendanceSource;
use App\Enums\Hr\AttendanceStatus;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrAttendanceRecord extends Model
{
    use BelongsToTenantConnection;

    protected $table = 'hr_attendance_records';

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'schedule_id',
        'attendance_location_id',
        'scheduled_start_at',
        'scheduled_end_at',
        'check_in_at',
        'check_out_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy',
        'check_in_distance_meters',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy',
        'check_out_distance_meters',
        'late_minutes',
        'early_leave_minutes',
        'worked_minutes',
        'status',
        'source',
        'admin_note',
        'adjusted_by',
        'adjusted_at',
        'ip_address',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'adjusted_at' => 'datetime',
        'status' => AttendanceStatus::class,
        'source' => AttendanceSource::class,
        'meta' => 'array',
        'check_in_latitude' => 'decimal:7',
        'check_in_longitude' => 'decimal:7',
        'check_out_latitude' => 'decimal:7',
        'check_out_longitude' => 'decimal:7',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(HrAttendanceSchedule::class, 'schedule_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(HrAttendanceLocation::class, 'attendance_location_id');
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'adjusted_by');
    }
}
