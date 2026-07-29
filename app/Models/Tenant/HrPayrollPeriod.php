<?php

namespace App\Models\Tenant;

use App\Enums\Hr\PayrollPeriodStatus;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrPayrollPeriod extends Model
{
    use BelongsToTenantConnection;

    protected $table = 'hr_payroll_periods';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'generated_at',
        'approved_at',
        'approved_by',
        'paid_at',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => PayrollPeriodStatus::class,
        'generated_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(HrPayrollEmployee::class, 'payroll_period_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'approved_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'paid_by');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [PayrollPeriodStatus::Approved, PayrollPeriodStatus::Paid], true);
    }
}
