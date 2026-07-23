<?php

namespace App\Enums\Erp;

enum ReturnDisposition: string
{
    case Restock = 'restock';
    case Damaged = 'damaged';
    case NotReceived = 'not_received';
    case Other = 'other';

    public function label(): string
    {
        return __('erp.return_dispositions.'.$this->value);
    }
}
