<?php

namespace App\Actions\Pos;

use App\Enums\Pos\CashierSessionStatus;
use App\Enums\Pos\CashMovementType;
use App\Models\Tenant\CashierSession;
use App\Models\Tenant\CashMovement;
use App\Services\Pos\CashMovementService;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CloseCashierSessionAction
{
    public function __construct(
        private readonly CashMovementService $movements,
    ) {}

    /**
     * @param  array{cash?: string|int|float, card?: string|int|float, transfer?: string|int|float, other?: string|int|float}|string  $actuals
     */
    public function execute(
        CashierSession $session,
        array|string $actuals,
        ?string $closingNotes = null,
        ?string $differenceReason = null,
    ): CashierSession {
        return DB::connection('tenant')->transaction(function () use ($session, $actuals, $closingNotes, $differenceReason) {
            /** @var CashierSession $locked */
            $locked = CashierSession::query()->whereKey($session->id)->lockForUpdate()->with(['cashMovements', 'register'])->firstOrFail();

            if ($locked->status === CashierSessionStatus::Closed) {
                throw ValidationException::withMessages([
                    'status' => __('commerce.validation.cashier_session_already_closed'),
                ]);
            }

            if ($locked->status === CashierSessionStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'status' => __('commerce.validation.cashier_session_cancelled'),
                ]);
            }

            if ($locked->status === CashierSessionStatus::Opened) {
                $this->transition($locked, CashierSessionStatus::Closing);
                $locked->closing_started_at = now();
                $locked->save();
            }

            if ($locked->status !== CashierSessionStatus::Closing) {
                throw ValidationException::withMessages([
                    'status' => __('commerce.validation.invalid_session_transition', [
                        'from' => $locked->status->value,
                        'to' => CashierSessionStatus::Closed->value,
                    ]),
                ]);
            }

            $expected = $this->calculateExpectedByTender($locked);
            $actual = $this->normalizeActuals($actuals);

            $expectedTotal = Decimal::money(
                Decimal::add(
                    Decimal::add($expected['cash'], $expected['card']),
                    Decimal::add($expected['transfer'], $expected['other'])
                )
            );
            $actualTotal = Decimal::money(
                Decimal::add(
                    Decimal::add($actual['cash'], $actual['card']),
                    Decimal::add($actual['transfer'], $actual['other'])
                )
            );
            $difference = Decimal::money(Decimal::sub($actualTotal, $expectedTotal));

            $locked->fill([
                'expected_cash' => $expected['cash'],
                'expected_card' => $expected['card'],
                'expected_transfer' => $expected['transfer'],
                'expected_other' => $expected['other'],
                'expected_balance' => $expectedTotal,
                'actual_cash' => $actual['cash'],
                'actual_card' => $actual['card'],
                'actual_transfer' => $actual['transfer'],
                'actual_other' => $actual['other'],
                'actual_balance' => $actualTotal,
                'difference' => $difference,
                'closing_notes' => $closingNotes,
                'difference_reason' => $differenceReason,
                'closed_at' => now(),
                'closed_by' => Auth::guard('tenant')->id(),
            ]);
            $locked->save();

            $this->movements->record($locked->fresh(['register']), CashMovementType::Closing, $actual['cash'], [
                'cash_drawer_id' => $locked->register?->cash_drawer_id,
                'payment_method_type' => 'cash',
                'direction' => 'out',
                'notes' => $closingNotes,
                'meta' => [
                    'actual' => $actual,
                    'expected' => $expected,
                    'difference' => $difference,
                ],
            ]);

            $this->transition($locked->fresh(), CashierSessionStatus::Closed);

            return $locked->fresh(['cashMovements']);
        });
    }

    /**
     * @return array{cash: string, card: string, transfer: string, other: string}
     */
    public function calculateExpectedByTender(CashierSession $session): array
    {
        $totals = [
            'cash' => Decimal::money($session->opening_balance ?? '0'),
            'card' => '0.00',
            'transfer' => '0.00',
            'other' => '0.00',
        ];

        $movements = $session->relationLoaded('cashMovements')
            ? $session->cashMovements
            : $session->cashMovements()->get();

        foreach ($movements as $movement) {
            /** @var CashMovement $movement */
            if (in_array($movement->type, [CashMovementType::Opening, CashMovementType::Closing], true)) {
                continue;
            }

            $tender = $this->resolveTender($movement);
            $amount = Decimal::money($movement->amount);
            $signed = $movement->direction === 'out'
                ? Decimal::sub('0', $amount)
                : $amount;

            $totals[$tender] = Decimal::money(Decimal::add($totals[$tender], $signed));
        }

        return $totals;
    }

    private function resolveTender(CashMovement $movement): string
    {
        $type = $movement->payment_method_type ?: 'cash';

        return match ($type) {
            'card' => 'card',
            'transfer' => 'transfer',
            'cash' => 'cash',
            default => 'other',
        };
    }

    /**
     * @param  array{cash?: string|int|float, card?: string|int|float, transfer?: string|int|float, other?: string|int|float}|string  $actuals
     * @return array{cash: string, card: string, transfer: string, other: string}
     */
    private function normalizeActuals(array|string $actuals): array
    {
        if (is_string($actuals) || is_numeric($actuals)) {
            return [
                'cash' => Decimal::money($actuals),
                'card' => '0.00',
                'transfer' => '0.00',
                'other' => '0.00',
            ];
        }

        return [
            'cash' => Decimal::money($actuals['cash'] ?? '0'),
            'card' => Decimal::money($actuals['card'] ?? '0'),
            'transfer' => Decimal::money($actuals['transfer'] ?? '0'),
            'other' => Decimal::money($actuals['other'] ?? '0'),
        ];
    }

    private function transition(CashierSession $session, CashierSessionStatus $next): void
    {
        if (! $session->status->canTransitionTo($next)) {
            throw ValidationException::withMessages([
                'status' => __('commerce.validation.invalid_session_transition', [
                    'from' => $session->status->value,
                    'to' => $next->value,
                ]),
            ]);
        }

        $session->status = $next;
        $session->save();
    }
}
