<?php

namespace App\Services\Pos;

use App\Enums\Pos\CashierSessionStatus;
use App\Enums\Pos\CashMovementType;
use App\Enums\Pos\ReceiptNumberStrategy;
use App\Models\Tenant\CashierSession;
use App\Models\Tenant\CashMovement;
use App\Models\Tenant\PosRegister;
use App\Models\Tenant\PosSetting;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CashierSessionService
{
    public function open(
        PosRegister $register,
        string $openingBalance = '0',
        ?string $deviceName = null,
        ?string $openingNotes = null,
    ): CashierSession {
        return DB::connection('tenant')->transaction(function () use ($register, $openingBalance, $deviceName, $openingNotes) {
            $settings = $this->settings();
            if ($settings->require_open_session && $register->openSession()) {
                throw ValidationException::withMessages([
                    'pos_register_id' => __('commerce.validation.cashier_session_already_open'),
                ]);
            }

            if ($register->openSession()) {
                throw ValidationException::withMessages([
                    'pos_register_id' => __('commerce.validation.cashier_session_already_open'),
                ]);
            }

            $userId = Auth::guard('tenant')->id();
            $session = CashierSession::query()->create([
                'pos_register_id' => $register->id,
                'branch_id' => $register->branch_id,
                'user_id' => $userId,
                'status' => CashierSessionStatus::Open,
                'device_name' => $deviceName,
                'opening_balance' => Decimal::money($openingBalance),
                'opening_notes' => $openingNotes,
                'opened_at' => now(),
            ]);

            CashMovement::query()->create([
                'cashier_session_id' => $session->id,
                'cash_drawer_id' => $register->cash_drawer_id,
                'type' => CashMovementType::Opening,
                'amount' => Decimal::money($openingBalance),
                'notes' => $openingNotes,
                'created_by' => $userId,
            ]);

            return $session->fresh();
        });
    }

    public function close(
        CashierSession $session,
        string $actualBalance,
        ?string $expectedBalance = null,
        ?string $closingNotes = null,
        ?string $differenceReason = null,
    ): CashierSession {
        return DB::connection('tenant')->transaction(function () use ($session, $actualBalance, $expectedBalance, $closingNotes, $differenceReason) {
            /** @var CashierSession $locked */
            $locked = CashierSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isOpen()) {
                return $locked;
            }

            $expected = Decimal::money($expectedBalance ?? $this->calculateExpectedBalance($locked));
            $actual = Decimal::money($actualBalance);
            $difference = Decimal::money(Decimal::sub($actual, $expected));

            $locked->fill([
                'status' => CashierSessionStatus::Closed,
                'expected_balance' => $expected,
                'actual_balance' => $actual,
                'difference' => $difference,
                'closing_notes' => $closingNotes,
                'difference_reason' => $differenceReason,
                'closed_at' => now(),
                'closed_by' => Auth::guard('tenant')->id(),
            ]);
            $locked->save();

            CashMovement::query()->create([
                'cashier_session_id' => $locked->id,
                'cash_drawer_id' => $locked->register?->cash_drawer_id,
                'type' => CashMovementType::Closing,
                'amount' => $actual,
                'notes' => $closingNotes,
                'created_by' => Auth::guard('tenant')->id(),
            ]);

            return $locked->fresh();
        });
    }

    public function calculateExpectedBalance(CashierSession $session): string
    {
        $total = Decimal::money($session->opening_balance ?? '0');

        foreach ($session->cashMovements as $movement) {
            if (in_array($movement->type, [CashMovementType::Opening, CashMovementType::Closing], true)) {
                continue;
            }

            $amount = Decimal::money($movement->amount);
            $total = match ($movement->type) {
                CashMovementType::CashIn, CashMovementType::PayIn => Decimal::add($total, $amount),
                CashMovementType::CashOut, CashMovementType::PayOut, CashMovementType::SafeDrop => Decimal::sub($total, $amount),
                default => $total,
            };
        }

        return Decimal::money($total);
    }

    public function settings(): PosSetting
    {
        $existing = PosSetting::query()->first();
        if ($existing) {
            return $existing;
        }

        return PosSetting::query()->create([
            'receipt_number_strategy' => ReceiptNumberStrategy::PerRegister,
            'require_open_session' => true,
            'allow_suspend_sales' => true,
            'allow_negative_stock' => false,
        ]);
    }
}
