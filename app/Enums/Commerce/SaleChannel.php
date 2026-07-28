<?php

namespace App\Enums\Commerce;

use App\Enums\Erp\SaleSourceType;

/**
 * Channel that initiates a sale through UnifiedSalesEngine.
 * Maps onto existing SaleSourceType for persistence.
 */
enum SaleChannel: string
{
    case Store = 'store';
    case Erp = 'erp';
    case Pos = 'pos';
    case Api = 'api';

    public function label(): string
    {
        return __('commerce.sale_channels.'.$this->value);
    }

    public function toSaleSourceType(): SaleSourceType
    {
        return match ($this) {
            self::Store => SaleSourceType::Store,
            self::Erp => SaleSourceType::Manual,
            self::Pos => SaleSourceType::Pos,
            self::Api => SaleSourceType::Api,
        };
    }

    public static function fromSaleSourceType(SaleSourceType $source): self
    {
        return match ($source) {
            SaleSourceType::Store, SaleSourceType::Order => self::Store,
            SaleSourceType::Pos => self::Pos,
            SaleSourceType::Api, SaleSourceType::Import => self::Api,
            SaleSourceType::Manual => self::Erp,
        };
    }
}
