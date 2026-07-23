<?php

namespace App\Enums\Erp;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';
    case Closed = 'closed';

    public function label(): string
    {
        return __('erp.purchase_order_statuses.'.$this->value);
    }
}
