<?php

namespace App\Services\Pos;

use App\Enums\Pos\CashierSessionStatus;
use App\Enums\Pos\CashMovementType;
use App\Models\Tenant\CashierSession;
use App\Models\Tenant\CashMovement;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Immutable cash drawer ledger. Corrections are reverse movements only.
 */
final class CashMovementService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function record(CashierSession $session, CashMovementType $type, string $amount, array $attributes = []): CashMovement
    {
        if ($session->status->isTerminal()) {
            throw ValidationException::withMessages([
                'cashier_session_id' => __('commerce.validation.cash_movement_session_closed'),
            ]);
        }

        $direction = $attributes['direction'] ?? $type->defaultDirection();
        if (! in_array($direction, ['in', 'out'], true)) {
            throw ValidationException::withMessages([
                'direction' => __('commerce.validation.cash_movement_direction_invalid'),
            ]);
        }

        return CashMovement::query()->create([
            'cashier_session_id' => $session->id,
            'cash_drawer_id' => $attributes['cash_drawer_id'] ?? $session->register?->cash_drawer_id,
            'type' => $type,
            'payment_method_type' => $attributes['payment_method_type'] ?? null,
            'payment_method_code' => $attributes['payment_method_code'] ?? null,
            'amount' => Decimal::money($amount),
            'direction' => $direction,
            'sale_id' => $attributes['sale_id'] ?? null,
            'sales_invoice_id' => $attributes['sales_invoice_id'] ?? null,
            'invoice_payment_id' => $attributes['invoice_payment_id'] ?? null,
            'reverses_movement_id' => $attributes['reverses_movement_id'] ?? null,
            'is_reversal' => (bool) ($attributes['is_reversal'] ?? false),
            'reference' => $attributes['reference'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'meta' => $attributes['meta'] ?? null,
            'created_by' => $attributes['created_by'] ?? Auth::guard('tenant')->id(),
        ]);
    }

    public function reverse(CashMovement $movement, ?string $notes = null): CashMovement
    {
        return DB::connection('tenant')->transaction(function () use ($movement, $notes) {
            /** @var CashMovement $locked */
            $locked = CashMovement::query()->whereKey($movement->id)->lockForUpdate()->firstOrFail();

            $already = CashMovement::query()
                ->where('reverses_movement_id', $locked->id)
                ->where('is_reversal', true)
                ->exists();

            if ($already) {
                throw ValidationException::withMessages([
                    'movement' => __('commerce.validation.cash_movement_already_reversed'),
                ]);
            }

            $session = CashierSession::query()->whereKey($locked->cashier_session_id)->lockForUpdate()->firstOrFail();
            if ($session->status === CashierSessionStatus::Closed || $session->status === CashierSessionStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'cashier_session_id' => __('commerce.validation.cash_movement_session_closed'),
                ]);
            }

            $reverseDirection = $locked->direction === 'in' ? 'out' : 'in';

            return $this->record($session, $locked->type, (string) $locked->amount, [
                'cash_drawer_id' => $locked->cash_drawer_id,
                'payment_method_type' => $locked->payment_method_type,
                'payment_method_code' => $locked->payment_method_code,
                'direction' => $reverseDirection,
                'sale_id' => $locked->sale_id,
                'sales_invoice_id' => $locked->sales_invoice_id,
                'invoice_payment_id' => $locked->invoice_payment_id,
                'reverses_movement_id' => $locked->id,
                'is_reversal' => true,
                'reference' => $locked->reference,
                'notes' => $notes ?? __('commerce.validation.cash_movement_reversal_note'),
                'meta' => array_merge($locked->meta ?? [], ['reversed_from' => $locked->id]),
            ]);
        });
    }

    public function assertImmutable(CashMovement $movement): void
    {
        throw new LogicException(__('commerce.validation.cash_movement_immutable'));
    }
}
