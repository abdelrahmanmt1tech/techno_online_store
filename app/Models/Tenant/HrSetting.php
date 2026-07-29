<?php

namespace App\Models\Tenant;

use App\Enums\Hr\AbsenceDeductionType;
use App\Enums\Hr\LateDeductionType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrSetting extends Model
{
    use BelongsToTenantConnection;

    protected $table = 'hr_settings';

    protected $fillable = [
        'default_attendance_schedule_id',
        'default_attendance_location_id',
        'payroll_day_of_month',
        'working_days_per_month',
        'default_absence_deduction_type',
        'default_absence_fixed_amount',
        'default_late_deduction_type',
        'default_late_fixed_amount',
        'default_late_amount_per_minute',
        'maximum_late_deduction_per_day',
        'auto_mark_absent',
        'absence_processing_time',
        'require_location_accuracy',
        'default_maximum_accuracy_meters',
    ];

    protected $casts = [
        'default_absence_deduction_type' => AbsenceDeductionType::class,
        'default_late_deduction_type' => LateDeductionType::class,
        'default_absence_fixed_amount' => 'decimal:2',
        'default_late_fixed_amount' => 'decimal:2',
        'default_late_amount_per_minute' => 'decimal:4',
        'maximum_late_deduction_per_day' => 'decimal:2',
        'auto_mark_absent' => 'boolean',
        'require_location_accuracy' => 'boolean',
    ];

    public function defaultSchedule(): BelongsTo
    {
        return $this->belongsTo(HrAttendanceSchedule::class, 'default_attendance_schedule_id');
    }

    public function defaultLocation(): BelongsTo
    {
        return $this->belongsTo(HrAttendanceLocation::class, 'default_attendance_location_id');
    }
}
