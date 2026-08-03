<?php

namespace App\Models\Tenant;

use App\Enums\Crm\CommissionAdjustmentStatus;
use App\Enums\Crm\CommissionPaymentEntryType;
use App\Enums\Crm\CommissionStatus;
use App\Enums\Crm\CommissionType;
use App\Services\Crm\Commission\CommissionAdjustmentCalculator;
use App\Support\Crm\CrmBranchVisibility;
use App\Support\Money\DecimalMath;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpportunityCommission extends Model
{
    use Concerns\BelongsToTenantConnection;

    use SoftDeletes;

    public const SOURCE_AUTOMATIC_WON = 'automatic_won';

    protected $fillable = [
        'opportunity_id',
        'user_id',
        'branch_id',
        'commission_type',
        'base_amount',
        'commission_percentage',
        'commission_amount',
        'paid_amount',
        'status',
        'source',
        'notes',
        'calculated_at',
        'approved_at',
        'approved_by',
        'created_by',
        'updated_by',
        'due_at',
        'last_manual_edit_field',
    ];

    protected function casts(): array
    {
        return [
            'commission_type' => CommissionType::class,
            'status' => CommissionStatus::class,
            'base_amount' => 'decimal:2',
            'commission_percentage' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'due_at' => 'date',
        ];
    }

    public function approvedIncreaseAdjustmentsTotal(): string
    {
        return CommissionAdjustmentCalculator::resolveApprovedIncreaseTotal($this);
    }

    public function approvedDecreaseAdjustmentsTotal(): string
    {
        return CommissionAdjustmentCalculator::resolveApprovedDecreaseTotal($this);
    }

    public function effectiveCommissionAmount(): string
    {
        return CommissionAdjustmentCalculator::effectiveCommissionAmount(
            (string) $this->commission_amount,
            $this->approvedIncreaseAdjustmentsTotal(),
            $this->approvedDecreaseAdjustmentsTotal(),
        );
    }

    public function netPaidAmount(): string
    {
        $payments = $this->resolvePaymentsTotal();
        $reversals = $this->resolveReversalsTotal();

        if (
            DecimalMath::isZero($payments)
            && DecimalMath::isZero($reversals)
            && DecimalMath::isPositive($this->paid_amount)
        ) {
            return DecimalMath::normalize($this->paid_amount);
        }

        return DecimalMath::sub($payments, $reversals);
    }

    protected function remainingAmount(): Attribute
    {
        return Attribute::get(fn (): string => DecimalMath::remaining(
            $this->effectiveCommissionAmount(),
            $this->netPaidAmount(),
        ));
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'updated_by');
    }

    public function commissionPayments(): HasMany
    {
        return $this->hasMany(CommissionPayment::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(OpportunityCommissionAdjustment::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(CommissionAuditLog::class, 'auditable');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeAutomatic(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_AUTOMATIC_WON);
    }

    public function scopeForBranch(Builder $query, int|array $branchIds): Builder
    {
        $branchIds = is_array($branchIds) ? $branchIds : [$branchIds];

        return $query->whereIn('branch_id', $branchIds);
    }

    public function scopeVisibleToUser(Builder $query, TenantUser $user): Builder
    {
        return CrmBranchVisibility::applyCommissionScope($query, $user);
    }

    /**
     * Eager-load approved adjustment totals and payment ledger sums for list/report queries.
     *
     * @param  Builder<OpportunityCommission>  $query
     * @return Builder<OpportunityCommission>
     */
    public function scopeWithFinancialAggregates(Builder $query): Builder
    {
        return $query
            ->withSum(['adjustments as approved_increase_adjustments_total' => function (Builder $adjustment): void {
                $adjustment
                    ->where('status', CommissionAdjustmentStatus::APPROVED)
                    ->where('direction', 'increase');
            }], 'amount')
            ->withSum(['adjustments as approved_decrease_adjustments_total' => function (Builder $adjustment): void {
                $adjustment
                    ->where('status', CommissionAdjustmentStatus::APPROVED)
                    ->where('direction', 'decrease');
            }], 'amount')
            ->withSum(['commissionPayments as payments_total' => function (Builder $payment): void {
                $payment->where('entry_type', CommissionPaymentEntryType::PAYMENT);
            }], 'amount')
            ->withSum(['commissionPayments as reversals_total' => function (Builder $payment): void {
                $payment->where('entry_type', CommissionPaymentEntryType::REVERSAL);
            }], 'amount');
    }

    private function resolvePaymentsTotal(): string
    {
        if (array_key_exists('payments_total', $this->getAttributes())) {
            return DecimalMath::normalize($this->getAttribute('payments_total'));
        }

        if ($this->relationLoaded('commissionPayments')) {
            return DecimalMath::normalize(
                (string) $this->commissionPayments
                    ->where('entry_type', CommissionPaymentEntryType::PAYMENT)
                    ->sum('amount'),
            );
        }

        return DecimalMath::normalize(
            (string) $this->commissionPayments()
                ->where('entry_type', CommissionPaymentEntryType::PAYMENT)
                ->sum('amount'),
        );
    }

    private function resolveReversalsTotal(): string
    {
        if (array_key_exists('reversals_total', $this->getAttributes())) {
            return DecimalMath::normalize($this->getAttribute('reversals_total'));
        }

        if ($this->relationLoaded('commissionPayments')) {
            return DecimalMath::normalize(
                (string) $this->commissionPayments
                    ->where('entry_type', CommissionPaymentEntryType::REVERSAL)
                    ->sum('amount'),
            );
        }

        return DecimalMath::normalize(
            (string) $this->commissionPayments()
                ->where('entry_type', CommissionPaymentEntryType::REVERSAL)
                ->sum('amount'),
        );
    }
}
