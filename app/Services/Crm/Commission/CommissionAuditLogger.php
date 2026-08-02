<?php

namespace App\Services\Crm\Commission;

use App\Models\Tenant\CommissionAuditLog;
use App\Models\Tenant\CommissionPaymentCycle;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;

final class CommissionAuditLogger
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public static function log(
        OpportunityCommission $commission,
        User $user,
        string $action,
        array $oldValues = [],
        array $newValues = [],
        ?string $amountBefore = null,
        ?string $amountAfter = null,
    ): CommissionAuditLog {
        return self::logFor($commission, $user, $action, $oldValues, $newValues, $amountBefore, $amountAfter);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public static function logFor(
        Model $auditable,
        User $user,
        string $action,
        array $oldValues = [],
        array $newValues = [],
        ?string $amountBefore = null,
        ?string $amountAfter = null,
    ): CommissionAuditLog {
        return $auditable->auditLogs()->create([
            'user_id' => $user->id,
            'action' => $action,
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'amount_before' => $amountBefore,
            'amount_after' => $amountAfter,
        ]);
    }

    public static function snapshot(OpportunityCommission $commission): array
    {
        return [
            'base_amount' => (string) $commission->base_amount,
            'commission_percentage' => (string) $commission->commission_percentage,
            'commission_amount' => (string) $commission->commission_amount,
            'status' => $commission->status->value,
        ];
    }

    public static function cycleSnapshot(CommissionPaymentCycle $cycle): array
    {
        return [
            'cycle_number' => $cycle->cycle_number,
            'status' => $cycle->status->value,
            'period_from' => $cycle->period_from?->toDateString(),
            'period_to' => $cycle->period_to?->toDateString(),
            'payment_date' => $cycle->payment_date?->toDateString(),
            'branch_id' => $cycle->branch_id,
        ];
    }
}
