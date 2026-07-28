<?php

namespace App\Actions\Pos;

use App\Enums\Erp\SaleStatus;
use App\Enums\Pos\CashierSessionStatus;
use App\Models\Tenant\CashierSession;
use App\Models\Tenant\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelCashierSessionAction
{
    public function execute(CashierSession $session, ?string $reason = null): CashierSession
    {
        return DB::connection('tenant')->transaction(function () use ($session, $reason) {
            /** @var CashierSession $locked */
            $locked = CashierSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(CashierSessionStatus::Cancelled)) {
                throw ValidationException::withMessages([
                    'status' => __('commerce.validation.invalid_session_transition', [
                        'from' => $locked->status->value,
                        'to' => CashierSessionStatus::Cancelled->value,
                    ]),
                ]);
            }

            $hasConfirmedSales = Sale::query()
                ->where('cashier_session_id', $locked->id)
                ->whereNotIn('status', [
                    SaleStatus::Draft->value,
                    SaleStatus::Cancelled->value,
                ])
                ->exists();

            if ($hasConfirmedSales) {
                throw ValidationException::withMessages([
                    'status' => __('commerce.validation.cannot_cancel_session_with_sales'),
                ]);
            }

            $locked->status = CashierSessionStatus::Cancelled;
            $locked->cancelled_at = now();
            $locked->cancelled_by = Auth::guard('tenant')->id();
            $locked->cancel_reason = $reason;
            $locked->save();

            return $locked->fresh();
        });
    }
}
