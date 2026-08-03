<?php

namespace App\Models\Tenant;

use App\Enums\Crm\CommissionPaymentCycleStatus;
use App\Models\TenantUser;
use App\Support\Crm\CrmBranchVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionPaymentCycle extends Model
{
    use Concerns\BelongsToTenantConnection;

    use SoftDeletes;

    protected $fillable = [
        'cycle_number',
        'period_from',
        'period_to',
        'status',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'branch_id',
        'created_by',
        'approved_by',
        'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommissionPaymentCycleStatus::class,
            'period_from' => 'date',
            'period_to' => 'date',
            'payment_date' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'approved_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'paid_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CommissionPaymentCycleAllocation::class);
    }

    public function commissionPayments(): HasMany
    {
        return $this->hasMany(CommissionPayment::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(CommissionAuditLog::class, 'auditable');
    }

    public function scopeForBranch(Builder $query, int|array $branchIds): Builder
    {
        $branchIds = is_array($branchIds) ? $branchIds : [$branchIds];

        return $query->whereIn('branch_id', $branchIds);
    }

    public function scopeVisibleToUser(Builder $query, TenantUser $user): Builder
    {
        return CrmBranchVisibility::applyCycleScope($query, $user);
    }
}
