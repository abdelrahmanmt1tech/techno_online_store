<?php

namespace App\Services\Crm\Commission;

use App\Enums\Crm\CommissionStatus;
use App\Models\Tenant\OpportunityCommission;
use App\Support\Crm\Commission\OpportunityCommissionState;
use App\Support\Money\DecimalMath;
use Illuminate\Validation\ValidationException;

final class CommissionPaymentCalculator
{
    public static function isCommissionPayable(OpportunityCommission $commission): bool
    {
        return OpportunityCommissionState::isPayable($commission);
    }

    public static function resolveCommissionStatusAfterPayment(
        string $effectiveAmount,
        string $netPaidAmount,
        string $remainingAmount,
    ): CommissionStatus {
        if (DecimalMath::isZero($remainingAmount)) {
            return CommissionStatus::PAID;
        }

        if (DecimalMath::isPositive($netPaidAmount)) {
            return CommissionStatus::PARTIALLY_PAID;
        }

        if (DecimalMath::isPositive($effectiveAmount)) {
            return CommissionStatus::APPROVED;
        }

        return CommissionStatus::APPROVED;
    }

    public static function assertPaymentAmount(string $paymentAmount, string $remainingAmount): void
    {
        CommissionCalculator::assertNonNegative($paymentAmount, 'planned_payment_amount');

        if (! DecimalMath::isPositive($paymentAmount)) {
            throw ValidationException::withMessages([
                'planned_payment_amount' => __('crm.commissions.validation.payment_amount_must_be_positive'),
            ]);
        }

        if (DecimalMath::compare($paymentAmount, $remainingAmount) === 1) {
            throw ValidationException::withMessages([
                'planned_payment_amount' => __('crm.commissions.validation.payment_amount_exceeds_remaining'),
            ]);
        }
    }

    public static function isFullPayment(string $paymentAmount, string $remainingAmount): bool
    {
        return DecimalMath::compare($paymentAmount, $remainingAmount) === 0;
    }
}
