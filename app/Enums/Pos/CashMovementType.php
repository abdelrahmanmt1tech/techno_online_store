<?php

namespace App\Enums\Pos;

enum CashMovementType: string
{
    case Opening = 'opening';
    case CashIn = 'cash_in';
    case CashOut = 'cash_out';
    case SalePayment = 'sale_payment';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
    case Closing = 'closing';
    case Transfer = 'transfer';
    /** @deprecated Prefer CashIn / SalePayment — kept for foundation rows */
    case SafeDrop = 'safe_drop';
    case PayIn = 'pay_in';
    case PayOut = 'pay_out';

    public function label(): string
    {
        return __('commerce.cash_movement_types.'.$this->value);
    }

    public function defaultDirection(): string
    {
        return match ($this) {
            self::Opening,
            self::CashIn,
            self::SalePayment,
            self::PayIn => 'in',
            self::CashOut,
            self::Refund,
            self::Closing,
            self::SafeDrop,
            self::PayOut,
            self::Transfer => 'out',
            self::Adjustment => 'in',
        };
    }

    public function affectsExpectedCash(): bool
    {
        return in_array($this, [
            self::Opening,
            self::CashIn,
            self::CashOut,
            self::SalePayment,
            self::Refund,
            self::Adjustment,
            self::SafeDrop,
            self::PayIn,
            self::PayOut,
        ], true);
    }
}
