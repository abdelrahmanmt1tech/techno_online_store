<?php

namespace App\Services\Accounting;

use App\Models\Tenant\Entry;
use App\Models\Payment;
use App\Models\Supplier;

/**
 * Presentation-only debit/credit for payment operations with safes/bank commission.
 * Stored entries stay as in {@see Payment::syncOperation()}; this only shapes how
 * commission appears on both debit and credit sides in UI/exports/print.
 */
final class PaymentCommissionEntryDisplay
{
    private const EPS = 0.0001;

    /**
     * @return array{debit: ?float, credit: ?float}
     */
    public static function displayDebitCredit(Entry $entry): array
    {
        $debit = $entry->debit !== null ? (float) $entry->debit : null;
        $credit = $entry->credit !== null ? (float) $entry->credit : null;

        $ctx = self::paymentCommissionContext($entry);
        if ($ctx === null) {
            return ['debit' => $debit, 'credit' => $credit];
        }

        [
            'amount' => $amount,
            'commission' => $commission,
            'safe_account_id' => $safeId,
            'linkable_account_id' => $linkableId,
            'is_payout' => $isPayout,
        ] = $ctx;

        $accId = (int) $entry->account_tree_id;

        if ($isPayout) {
            if (
                $accId === $linkableId
                && $debit !== null
                && $credit === null
                && abs($debit - $amount) <= max(self::EPS, abs($amount) * 1e-9)
            ) {
                return [
                    'debit' => round($amount + $commission, 2),
                    'credit' => round($commission, 2),
                ];
            }

            return ['debit' => $debit, 'credit' => $credit];
        }

        // Receipt: safe line shows gross debit and commission on the credit side.
        if (
            $accId === $safeId
            && $debit !== null
            && $credit === null
        ) {
            $expectedNet = max(0.0, round($amount - $commission, 2));
            if (abs($debit - $expectedNet) <= max(self::EPS, abs($amount) * 1e-9)) {
                return [
                    'debit' => round($amount, 2),
                    'credit' => round($commission, 2),
                ];
            }
        }

        return ['debit' => $debit, 'credit' => $credit];
    }

    public static function settlementBreakdownForPayment(?Payment $payment): string
    {
        if (! $payment) {
            return '-';
        }

        $amount = (float) ($payment->amount ?? 0);
        $commission = (float) ($payment->commission_amount ?? 0);
        $effect = (float) ($payment->safes_bank_effect ?? 0);

        if ($amount <= 0) {
            return '-';
        }

        if ($commission <= self::EPS) {
            return number_format($amount, 2, '.', '');
        }

        if ($effect >= 0) {
            return number_format($amount, 2, '.', '')
                . ' - ' . number_format($commission, 2, '.', '')
                . ' = ' . number_format($effect, 2, '.', '');
        }

        return number_format($amount, 2, '.', '')
            . ' + ' . number_format($commission, 2, '.', '')
            . ' = ' . number_format(abs($effect), 2, '.', '');
    }

    public static function settlementBreakdownForEntry(Entry $entry): string
    {
        $op = $entry->operation;
        if (! $op || $op->service_type !== Payment::class) {
            return '-';
        }

        $service = $op->service;

        return self::settlementBreakdownForPayment($service instanceof Payment ? $service : null);
    }

    /**
     * @return array{
     *     amount: float,
     *     commission: float,
     *     safe_account_id: int,
     *     linkable_account_id: int,
     *     is_payout: bool
     * }|null
     */
    private static function paymentCommissionContext(Entry $entry): ?array
    {
        $op = $entry->operation;
        if (! $op || $op->service_type !== Payment::class) {
            return null;
        }

        $payment = $op->service;
        if (! $payment instanceof Payment) {
            return null;
        }

        $payment->loadMissing(['safesBank', 'paymentable']);

        $amount = (float) ($payment->amount ?? 0);
        $commission = (float) ($payment->commission_amount ?? 0);

        if ($amount <= 0 || $commission <= self::EPS) {
            return null;
        }

        $safeId = (int) ($payment->safesBank?->account_tree_id ?? 0);
        $linkableId = (int) ($payment->paymentable?->account_tree_id ?? 0);

        if ($safeId <= 0 || $linkableId <= 0) {
            return null;
        }

        $isPayout = ($payment->paymentable_type === Supplier::class)
            || (bool) ($payment->is_refund ?? false);

        return [
            'amount' => $amount,
            'commission' => $commission,
            'safe_account_id' => $safeId,
            'linkable_account_id' => $linkableId,
            'is_payout' => $isPayout,
        ];
    }
}
