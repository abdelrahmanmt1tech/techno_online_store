<?php

namespace App\Services\Pos;

use App\Actions\Pos\CancelCashierSessionAction;
use App\Actions\Pos\CloseCashierSessionAction;
use App\Actions\Pos\OpenCashierSessionAction;
use App\Enums\Pos\ReceiptNumberStrategy;
use App\Models\Tenant\CashierSession;
use App\Models\Tenant\PosRegister;
use App\Models\Tenant\PosSetting;
use App\Support\Erp\Decimal;

/**
 * Facade for session lifecycle — delegates to Actions.
 */
final class CashierSessionService
{
    public function __construct(
        private readonly OpenCashierSessionAction $openAction,
        private readonly CloseCashierSessionAction $closeAction,
        private readonly CancelCashierSessionAction $cancelAction,
    ) {}

    public function open(
        PosRegister $register,
        string $openingBalance = '0',
        ?string $deviceName = null,
        ?string $openingNotes = null,
    ): CashierSession {
        return $this->openAction->execute($register, $openingBalance, $deviceName, $openingNotes);
    }

    /**
     * @param  array{cash?: string|int|float, card?: string|int|float, transfer?: string|int|float, other?: string|int|float}|string  $actuals
     */
    public function close(
        CashierSession $session,
        array|string $actuals,
        ?string $expectedOrNotes = null,
        ?string $notesOrReason = null,
        ?string $differenceReason = null,
    ): CashierSession {
        if (is_array($actuals)) {
            return $this->closeAction->execute($session, $actuals, $expectedOrNotes, $notesOrReason);
        }

        // Legacy: close($session, $actualCash, $expectedCash, $notes, $reason?)
        if ($notesOrReason !== null) {
            return $this->closeAction->execute($session, $actuals, $notesOrReason, $differenceReason);
        }

        return $this->closeAction->execute($session, $actuals, $expectedOrNotes, $differenceReason);
    }

    public function cancel(CashierSession $session, ?string $reason = null): CashierSession
    {
        return $this->cancelAction->execute($session, $reason);
    }

    public function calculateExpectedBalance(CashierSession $session): string
    {
        $byTender = $this->closeAction->calculateExpectedByTender($session->loadMissing('cashMovements'));

        return Decimal::money(
            Decimal::add(
                Decimal::add($byTender['cash'], $byTender['card']),
                Decimal::add($byTender['transfer'], $byTender['other'])
            )
        );
    }

    public function settings(): PosSetting
    {
        $existing = PosSetting::query()->first();
        if ($existing) {
            return $existing;
        }

        return PosSetting::query()->create([
            'receipt_number_strategy' => ReceiptNumberStrategy::BranchRegisterDate,
            'require_open_session' => true,
            'allow_suspend_sales' => true,
            'allow_negative_stock' => false,
            'suspend_expires_minutes' => 120,
        ]);
    }
}
