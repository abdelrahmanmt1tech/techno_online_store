<?php

namespace App\Actions\Pos;

use App\Enums\Pos\CashierSessionStatus;
use App\Enums\Pos\CashMovementType;
use App\Models\Tenant\CashierSession;
use App\Models\Tenant\PosRegister;
use App\Services\Pos\CashMovementService;
use App\Services\Pos\PosRegisterGuard;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OpenCashierSessionAction
{
    public function __construct(
        private readonly PosRegisterGuard $guard,
        private readonly CashMovementService $movements,
    ) {}

    public function execute(
        PosRegister $register,
        string $openingBalance = '0',
        ?string $deviceName = null,
        ?string $openingNotes = null,
    ): CashierSession {
        return DB::connection('tenant')->transaction(function () use ($register, $openingBalance, $deviceName, $openingNotes) {
            $user = $this->guard->assertCanOpen($register);

            /** @var PosRegister $lockedRegister */
            $lockedRegister = PosRegister::query()->whereKey($register->id)->lockForUpdate()->firstOrFail();

            $registerOpen = CashierSession::query()
                ->where('pos_register_id', $lockedRegister->id)
                ->whereIn('status', [
                    CashierSessionStatus::Opening->value,
                    CashierSessionStatus::Opened->value,
                    CashierSessionStatus::Closing->value,
                ])
                ->exists();

            if ($registerOpen) {
                throw ValidationException::withMessages([
                    'pos_register_id' => __('commerce.validation.cashier_session_already_open'),
                ]);
            }

            $userOpen = CashierSession::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    CashierSessionStatus::Opening->value,
                    CashierSessionStatus::Opened->value,
                    CashierSessionStatus::Closing->value,
                ])
                ->exists();

            if ($userOpen) {
                throw ValidationException::withMessages([
                    'user_id' => __('commerce.validation.cashier_user_already_has_session'),
                ]);
            }

            $session = CashierSession::query()->create([
                'pos_register_id' => $lockedRegister->id,
                'branch_id' => $lockedRegister->branch_id,
                'user_id' => $user->id,
                'status' => CashierSessionStatus::Opening,
                'device_name' => $deviceName,
                'opening_balance' => Decimal::money($openingBalance),
                'opening_notes' => $openingNotes,
                'opened_at' => now(),
            ]);

            $this->transition($session, CashierSessionStatus::Opened);

            $this->movements->record($session->fresh(), CashMovementType::Opening, $openingBalance, [
                'cash_drawer_id' => $lockedRegister->cash_drawer_id,
                'payment_method_type' => 'cash',
                'direction' => 'in',
                'notes' => $openingNotes,
                'created_by' => $user->id,
            ]);

            return $session->fresh(['cashMovements', 'register']);
        });
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
