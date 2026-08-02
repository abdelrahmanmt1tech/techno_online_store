<?php

namespace App\Support\Crm\Commission;

use App\Enums\Crm\CommissionStatus;

final class OwnCommissionVisibility
{
    /**
     * Default list: employee sees active pipeline commissions.
     * Draft is hidden — not yet submitted for employee review.
     */
    public static function defaultStatuses(): array
    {
        return [
            CommissionStatus::PENDING,
            CommissionStatus::APPROVED,
            CommissionStatus::PARTIALLY_PAID,
            CommissionStatus::PAID,
        ];
    }

    public static function historyStatuses(): array
    {
        return [
            CommissionStatus::REJECTED,
            CommissionStatus::CANCELLED,
        ];
    }

    /**
     * @return list<CommissionStatus>
     */
    public static function statusesForList(bool $includeHistory = false, bool $includeDraft = false): array
    {
        $statuses = self::defaultStatuses();

        if ($includeHistory) {
            $statuses = array_merge($statuses, self::historyStatuses());
        }

        if ($includeDraft) {
            $statuses[] = CommissionStatus::DRAFT;
        }

        return array_values(array_unique($statuses, SORT_REGULAR));
    }

    public static function isIncludedInPayableTotals(CommissionStatus $status): bool
    {
        return in_array($status, [
            CommissionStatus::APPROVED,
            CommissionStatus::PARTIALLY_PAID,
            CommissionStatus::PAID,
        ], true);
    }

    public static function isPendingReview(CommissionStatus $status): bool
    {
        return $status === CommissionStatus::PENDING;
    }
}
