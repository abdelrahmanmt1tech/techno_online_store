<?php

namespace App\Enums\Erp;

enum SaleSourceType: string
{
    case Manual = 'manual';
    case Order = 'order';
    case Pos = 'pos';
    case Import = 'import';

    public function label(): string
    {
        return __('erp.sale_source_types.'.$this->value);
    }
}
