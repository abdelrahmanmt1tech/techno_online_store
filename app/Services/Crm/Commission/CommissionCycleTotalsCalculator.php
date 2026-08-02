<?php

namespace App\Services\Crm\Commission;

use App\Enums\Crm\CommissionPaymentEntryType;
use App\Models\Tenant\CommissionPaymentCycle;
use App\Support\Money\DecimalMath;

final class CommissionCycleTotalsCalculator
{
    /**
     * @return array{total_paid: string, total_reversed: string, net_paid: string}
     */
    public static function forCycle(CommissionPaymentCycle $cycle): array
    {
        $totalPaid = DecimalMath::normalize(
            (string) $cycle->commissionPayments()
                ->where('entry_type', CommissionPaymentEntryType::PAYMENT)
                ->sum('amount'),
        );

        $totalReversed = DecimalMath::normalize(
            (string) $cycle->commissionPayments()
                ->where('entry_type', CommissionPaymentEntryType::REVERSAL)
                ->sum('amount'),
        );

        return [
            'total_paid' => $totalPaid,
            'total_reversed' => $totalReversed,
            'net_paid' => DecimalMath::sub($totalPaid, $totalReversed),
        ];
    }

    public static function plannedTotal(CommissionPaymentCycle $cycle): string
    {
        return DecimalMath::normalize(
            (string) $cycle->allocations()->sum('planned_payment_amount'),
        );
    }
}
