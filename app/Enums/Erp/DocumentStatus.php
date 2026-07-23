<?php

namespace App\Enums\Erp;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('erp.document_statuses.'.$this->value);
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
