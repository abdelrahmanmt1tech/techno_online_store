<?php

namespace App\Models\Tenant;

use App\Enums\Hr\AbsenceDeductionType;
use App\Enums\Hr\EmploymentStatus;
use App\Enums\Hr\SalaryType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrEmployee extends Model
{
    use BelongsToTenantConnection;

    protected $table = 'hr_employees';

    protected $fillable = [
        'employee_number',
        'user_id',
        'full_name',
        'email',
        'phone',
        'branch_id',
        'department_id',
        'job_title_id',
        'attendance_schedule_id',
        'attendance_location_id',
        'hire_date',
        'employment_status',
        'salary_type',
        'base_salary',
        'custom_late_grace_minutes',
        'custom_absence_deduction_type',
        'custom_absence_deduction_value',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'employment_status' => EmploymentStatus::class,
        'salary_type' => SalaryType::class,
        'custom_absence_deduction_type' => AbsenceDeductionType::class,
        'base_salary' => 'decimal:2',
        'custom_absence_deduction_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(HrDepartment::class, 'department_id');
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(HrJobTitle::class, 'job_title_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(HrAttendanceSchedule::class, 'attendance_schedule_id');
    }

    public function attendanceLocation(): BelongsTo
    {
        return $this->belongsTo(HrAttendanceLocation::class, 'attendance_location_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(HrAttendanceRecord::class, 'employee_id');
    }

    public function payrollLines(): HasMany
    {
        return $this->hasMany(HrPayrollEmployee::class, 'employee_id');
    }

    public function isOperationallyActive(): bool
    {
        return $this->is_active && $this->employment_status === EmploymentStatus::Active;
    }
}
