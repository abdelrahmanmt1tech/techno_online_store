<?php

namespace App\Filament\Tenant\Resources\Coupons\Pages;

use App\Filament\Tenant\Resources\Coupons\CouponResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;
}
