<?php

namespace App\Enums\Commerce;

enum ProductVisibility: string
{
    case Visible = 'visible';
    case Hidden = 'hidden';
    case CatalogOnly = 'catalog_only';
    case PosOnly = 'pos_only';

    public function label(): string
    {
        return __('commerce.product_visibilities.'.$this->value);
    }

    public function visibleOnStorefront(): bool
    {
        return in_array($this, [self::Visible, self::CatalogOnly], true);
    }

    public function visibleOnPos(): bool
    {
        return in_array($this, [self::Visible, self::PosOnly], true);
    }
}
