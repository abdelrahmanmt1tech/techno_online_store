<?php

namespace App\Enums\Erp;

enum SaleStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case PartiallyInvoiced = 'partially_invoiced';
    case Invoiced = 'invoiced';
    case PartiallyReturned = 'partially_returned';
    case Returned = 'returned';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('erp.sale_statuses.'.$this->value);
    }

    public function isActiveForOrderLink(): bool
    {
        return ! in_array($this, [self::Cancelled, self::Reversed], true);
    }
}
