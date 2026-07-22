<?php

namespace App\Enums\Erp;

enum StockTransactionType: string
{
    case OpeningBalance = 'opening_balance';
    case ManualReceipt = 'manual_receipt';
    case ManualIssue = 'manual_issue';
    case Transfer = 'transfer';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case Damage = 'damage';
    case PurchaseReceipt = 'purchase_receipt';
    case PurchaseReturn = 'purchase_return';
    case SaleIssue = 'sale_issue';
    case SaleReturn = 'sale_return';
    case Reversal = 'reversal';

    public function label(): string
    {
        return __('erp.stock_transaction_types.'.$this->value);
    }

    public function isInbound(): bool
    {
        return in_array($this, [
            self::OpeningBalance,
            self::ManualReceipt,
            self::AdjustmentIn,
            self::PurchaseReceipt,
            self::SaleReturn,
        ], true);
    }

    public function isOutbound(): bool
    {
        return in_array($this, [
            self::ManualIssue,
            self::AdjustmentOut,
            self::Damage,
            self::PurchaseReturn,
            self::SaleIssue,
        ], true);
    }

    public function isTransfer(): bool
    {
        return $this === self::Transfer;
    }
}
