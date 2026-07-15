<?php

namespace App\Filament\Tenant\Resources\Coupons\Pages;

use App\Filament\Tenant\Resources\Coupons\CouponResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoupon extends EditRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
