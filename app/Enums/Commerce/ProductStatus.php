<?php

namespace App\Enums\Commerce;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return __('commerce.product_statuses.'.$this->value);
    }

    public function isSellable(): bool
    {
        return $this === self::Active;
    }
}
