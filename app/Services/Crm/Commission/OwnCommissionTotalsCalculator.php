<?php

namespace App\Services\Crm\Commission;

use App\Enums\Crm\CommissionStatus;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Support\Crm\Commission\OwnCommissionVisibility;
use App\Support\Money\DecimalMath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class OwnCommissionTotalsCalculator
{
    /**
     * @return array{
     *     original_total: string,
     *     approved_increase_total: string,
     *     approved_decrease_total: string,
     *     effective_total: string,
     *     net_paid_total: string,
     *     remaining_total: string,
     *     pending_count: int,
     *     approved_count: int,
     *     opportunity_count: int,
     * }
     */
    public static function forUser(TenantUser $user, ?Builder $scopedQuery = null): array
    {
        $query = $scopedQuery ?? OwnCommissionQuery::forUser($user, includeHistory: true);

        /** @var Collection<int, OpportunityCommission> $commissions */
        $commissions = (clone $query)
            ->withFinancialAggregates()
            ->with(['adjustments', 'commissionPayments'])
            ->get();

        $originalTotal = '0.00';
        $increaseTotal = '0.00';
        $decreaseTotal = '0.00';
        $effectiveTotal = '0.00';
        $netPaidTotal = '0.00';
        $remainingTotal = '0.00';
        $pendingCount = 0;
        $approvedCount = 0;
        $opportunityIds = [];

        foreach ($commissions as $commission) {
            if ($commission->status === CommissionStatus::DRAFT) {
                continue;
            }

            $originalTotal = DecimalMath::add($originalTotal, (string) $commission->commission_amount);
            $increaseTotal = DecimalMath::add($increaseTotal, $commission->approvedIncreaseAdjustmentsTotal());
            $decreaseTotal = DecimalMath::add($decreaseTotal, $commission->approvedDecreaseAdjustmentsTotal());

            if (OwnCommissionVisibility::isPendingReview($commission->status)) {
                $pendingCount++;
            }

            if ($commission->status === CommissionStatus::APPROVED) {
                $approvedCount++;
            }

            $opportunityIds[$commission->opportunity_id] = true;

            if (! OwnCommissionVisibility::isIncludedInPayableTotals($commission->status)
                && ! in_array($commission->status, OwnCommissionVisibility::historyStatuses(), true)) {
                continue;
            }

            if (in_array($commission->status, OwnCommissionVisibility::historyStatuses(), true)) {
                continue;
            }

            $effective = $commission->effectiveCommissionAmount();
            $netPaid = $commission->netPaidAmount();
            $remaining = DecimalMath::remaining($effective, $netPaid);

            $effectiveTotal = DecimalMath::add($effectiveTotal, $effective);
            $netPaidTotal = DecimalMath::add($netPaidTotal, $netPaid);
            $remainingTotal = DecimalMath::add($remainingTotal, $remaining);
        }

        return [
            'original_total' => DecimalMath::normalize($originalTotal),
            'approved_increase_total' => DecimalMath::normalize($increaseTotal),
            'approved_decrease_total' => DecimalMath::normalize($decreaseTotal),
            'effective_total' => DecimalMath::normalize($effectiveTotal),
            'net_paid_total' => DecimalMath::normalize($netPaidTotal),
            'remaining_total' => DecimalMath::normalize($remainingTotal),
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'opportunity_count' => count($opportunityIds),
        ];
    }
}
