<?php

namespace App\Models\Tenant;

use App\Enums\Hr\SalaryType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrPayrollEmployee extends Model
{
    use BelongsToTenantConnection;

    protected $table = 'hr_payroll_employees';

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'base_salary_snapshot',
        'salary_type_snapshot',
        'working_days_count',
        'present_days',
        'late_days',
        'absent_days',
        'total_late_minutes',
        'absence_deduction',
        'late_deduction',
        'manual_deduction',
        'manual_deduction_reason',
        'total_deductions',
        'net_salary',
        'calculation_details',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'salary_type_snapshot' => SalaryType::class,
        'base_salary_snapshot' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'manual_deduction' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'calculation_details' => 'array',
        'paid_at' => 'datetime',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(HrPayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
