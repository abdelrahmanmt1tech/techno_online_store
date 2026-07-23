<?php

namespace App\Enums\Erp;

enum CommerceSourceType: string
{
    case Product = 'product';
    case ProductVariant = 'product_variant';

    public function label(): string
    {
        return __('erp.commerce_source_types.'.$this->value);
    }
}
