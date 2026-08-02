<?php

namespace App\Services\Crm\Commission;

use App\Enums\Crm\CommissionStatus;
use App\Enums\Crm\CommissionType;
use App\Enums\Crm\OpportunityStageAction;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Support\Money\DecimalMath;
use Illuminate\Support\Facades\Log;

/**
 * Domain service: creates a pending sales commission automatically when an opportunity is closed
 * as WON (OpportunityStageAction::SUCCESS_CLOSE). Centralised so any path that transitions a stage
 * (Filament, services, future events) gets the same behaviour — never tied to a Filament page.
 *
 * Guarantees:
 * - Idempotent: at most one automatic sales commission per (opportunity, employee) — relies on the
 *   existing unique (opportunity_id, user_id, commission_type) constraint + an explicit pre-check.
 * - Copies opportunity.amount into agreed_amount when the latter is empty, before calculating.
 * - Uses DecimalMath/BCMath — never float.
 * - Skips silently (logged, not an error) when there is no assigned employee, no/zero percentage,
 *   no base amount, or a commission already exists.
 * - Must run inside the caller's transaction so the close + commission are atomic.
 */
final class AutomaticOpportunityCommissionService
{
    public const COMMISSION_TYPE = CommissionType::SALES;

    public function handleStageTransition(
        Opportunity $opportunity,
        OpportunityStageAction $newAction,
        ?TenantUser $actor,
    ): ?OpportunityCommission {
        if ($newAction !== OpportunityStageAction::SUCCESS_CLOSE) {
            return null;
        }

        return $this->handleWon($opportunity, $actor);
    }

    private function handleWon(Opportunity $opportunity, ?TenantUser $actor): ?OpportunityCommission
    {
        $employeeId = $opportunity->assigned_to !== null ? (int) $opportunity->assigned_to : null;

        if ($employeeId === null) {
            $this->logSkip($opportunity, 'no_assigned_employee', $actor);

            return null;
        }

        $employee = TenantUser::query()->find($employeeId);

        if (! $employee instanceof TenantUser) {
            $this->logSkip($opportunity, 'employee_not_found', $actor, ['employee_id' => $employeeId]);

            return null;
        }

        $percentage = $employee->defaultOpportunityCommissionPercentage();

        if ($percentage === null) {
            $this->logSkip($opportunity, 'no_commission_percentage', $actor, ['employee_id' => $employeeId]);

            return null;
        }

        if (DecimalMath::isZero($percentage) || DecimalMath::isNegative($percentage)) {
            $this->logSkip($opportunity, 'zero_commission_percentage', $actor, ['employee_id' => $employeeId]);

            return null;
        }

        $this->backfillAgreedAmount($opportunity, $actor);

        $baseAmount = CommissionCalculator::defaultBaseAmount($opportunity);

        if (DecimalMath::isZero($baseAmount)) {
            $this->logSkip($opportunity, 'zero_base_amount', $actor, ['employee_id' => $employeeId]);

            return null;
        }

        $existing = OpportunityCommission::query()
            ->where('opportunity_id', $opportunity->id)
            ->where('user_id', $employeeId)
            ->where('commission_type', self::COMMISSION_TYPE->value)
            ->first();

        if ($existing !== null) {
            $this->logSkip($opportunity, 'commission_already_exists', $actor, [
                'employee_id' => $employeeId,
                'commission_id' => $existing->id,
            ]);

            return $existing;
        }

        $percentage = DecimalMath::normalize($percentage);
        $commissionAmount = CommissionCalculator::amountFromPercentage($baseAmount, $percentage);

        $commission = OpportunityCommission::query()->create([
            'opportunity_id' => $opportunity->id,
            'user_id' => $employeeId,
            'branch_id' => $opportunity->branch_id,
            'commission_type' => self::COMMISSION_TYPE,
            'base_amount' => $baseAmount,
            'commission_percentage' => $percentage,
            'commission_amount' => $commissionAmount,
            'status' => CommissionStatus::PENDING,
            'source' => OpportunityCommission::SOURCE_AUTOMATIC_WON,
            'notes' => __('crm.commissions.automatic.notes'),
            'calculated_at' => now(),
            'created_by' => $actor?->id,
            'updated_by' => $actor?->id,
        ]);

        if ($actor instanceof TenantUser) {
            $metadata = [
                'opportunity_id' => $opportunity->id,
                'employee_id' => $employeeId,
                'percentage' => $percentage,
                'base_amount' => $baseAmount,
                'commission_amount' => $commissionAmount,
                'stage_action' => OpportunityStageAction::SUCCESS_CLOSE->value,
                'actor_id' => $actor->id,
                'source' => OpportunityCommission::SOURCE_AUTOMATIC_WON,
            ];

            CommissionAuditLogger::log(
                $commission,
                $actor,
                'automatic_created',
                [],
                $metadata,
                null,
                $commissionAmount,
            );

            CommissionAuditLogger::log(
                $commission,
                $actor,
                'submitted_for_review',
                ['status' => CommissionStatus::DRAFT->value],
                ['status' => CommissionStatus::PENDING->value],
            );
        }

        return $commission;
    }

    private function backfillAgreedAmount(Opportunity $opportunity, ?TenantUser $actor): void
    {
        $agreedEmpty = $opportunity->agreed_amount === null || DecimalMath::isZero($opportunity->agreed_amount);
        $amountPositive = $opportunity->amount !== null && DecimalMath::isPositive($opportunity->amount);

        if (! $agreedEmpty || ! $amountPositive) {
            return;
        }

        $opportunity->agreed_amount = DecimalMath::normalize((string) $opportunity->amount);
        $opportunity->save();

        Log::info('crm.automatic_commission.agreed_amount_copied_from_amount', [
            'opportunity_id' => $opportunity->id,
            'amount' => (string) $opportunity->amount,
            'actor_id' => $actor?->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logSkip(Opportunity $opportunity, string $reason, ?TenantUser $actor, array $context = []): void
    {
        Log::info('crm.automatic_commission.skipped', array_merge([
            'opportunity_id' => $opportunity->id,
            'reason' => $reason,
            'actor_id' => $actor?->id,
        ], $context));
    }
}
