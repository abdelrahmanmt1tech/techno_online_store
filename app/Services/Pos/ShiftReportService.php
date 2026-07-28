<?php

namespace App\Services\Pos;

use App\Actions\Pos\CloseCashierSessionAction;
use App\Enums\Erp\SaleStatus;
use App\Enums\Pos\CashMovementType;
use App\Models\Tenant\CashierSession;
use App\Models\Tenant\CashMovement;
use App\Models\Tenant\Sale;
use App\Support\Erp\Decimal;

/**
 * X / Z / Shift summary reports — services only (no UI).
 */
final class ShiftReportService
{
    public function __construct(
        private readonly CloseCashierSessionAction $closeAction,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function xReport(CashierSession $session): array
    {
        return $this->build($session, 'x');
    }

    /**
     * @return array<string, mixed>
     */
    public function zReport(CashierSession $session): array
    {
        return $this->build($session, 'z');
    }

    /**
     * @return array<string, mixed>
     */
    public function shiftSummary(CashierSession $session): array
    {
        return $this->build($session, 'summary');
    }

    /**
     * @return array<string, mixed>
     */
    private function build(CashierSession $session, string $kind): array
    {
        $session->loadMissing(['cashMovements', 'sales.items', 'register', 'user', 'branch']);

        $sales = $session->sales;
        $completed = $sales->filter(fn (Sale $s) => in_array($s->status, [
            SaleStatus::Confirmed,
            SaleStatus::PartiallyInvoiced,
            SaleStatus::Invoiced,
            SaleStatus::PartiallyReturned,
            SaleStatus::Returned,
        ], true));
        $cancelled = $sales->filter(fn (Sale $s) => $s->status === SaleStatus::Cancelled);
        $suspended = $sales->filter(fn (Sale $s) => (bool) $s->is_suspended);

        $salesAmount = '0.00';
        $taxTotal = '0.00';
        $discountTotal = '0.00';
        foreach ($completed as $sale) {
            $salesAmount = Decimal::money(Decimal::add($salesAmount, (string) $sale->grand_total));
            $taxTotal = Decimal::money(Decimal::add($taxTotal, (string) $sale->tax_total));
            $discountTotal = Decimal::money(Decimal::add($discountTotal, (string) $sale->discount_total));
        }

        $salesCount = $completed->count();
        $averageSale = $salesCount > 0
            ? Decimal::money(Decimal::div($salesAmount, (string) $salesCount))
            : '0.00';

        $refunds = '0.00';
        $paymentsByMethod = [];
        foreach ($session->cashMovements as $movement) {
            /** @var CashMovement $movement */
            if ($movement->type === CashMovementType::Refund) {
                $refunds = Decimal::money(Decimal::add($refunds, (string) $movement->amount));
            }
            if ($movement->type === CashMovementType::SalePayment && ! $movement->is_reversal) {
                $key = $movement->payment_method_type ?: 'other';
                $paymentsByMethod[$key] = Decimal::money(
                    Decimal::add($paymentsByMethod[$key] ?? '0', (string) $movement->amount)
                );
            }
        }

        $expectedByTender = $this->closeAction->calculateExpectedByTender($session);
        $expectedTotal = Decimal::money(
            Decimal::add(
                Decimal::add($expectedByTender['cash'], $expectedByTender['card']),
                Decimal::add($expectedByTender['transfer'], $expectedByTender['other'])
            )
        );

        return [
            'report_type' => $kind,
            'session_id' => $session->id,
            'status' => $session->status->value,
            'register_id' => $session->pos_register_id,
            'branch_id' => $session->branch_id,
            'user_id' => $session->user_id,
            'opened_at' => optional($session->opened_at)?->toIso8601String(),
            'closed_at' => optional($session->closed_at)?->toIso8601String(),
            'sales_count' => $salesCount,
            'sales_amount' => $salesAmount,
            'refunds' => $refunds,
            'payments_by_method' => $paymentsByMethod,
            'opening_balance' => Decimal::money($session->opening_balance ?? '0'),
            'expected_by_tender' => $expectedByTender,
            'expected_balance' => $expectedTotal,
            'actual_by_tender' => [
                'cash' => $session->actual_cash,
                'card' => $session->actual_card,
                'transfer' => $session->actual_transfer,
                'other' => $session->actual_other,
            ],
            'actual_balance' => $session->actual_balance !== null ? Decimal::money($session->actual_balance) : null,
            'difference' => $session->difference !== null ? Decimal::money($session->difference) : null,
            'net_cash' => $expectedByTender['cash'],
            'taxes' => $taxTotal,
            'discounts' => $discountTotal,
            'average_sale' => $averageSale,
            'cancelled_sales' => $cancelled->count(),
            'suspended_sales' => $suspended->count(),
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
