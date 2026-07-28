<?php

namespace App\Filament\Tenant\Resources\PosPaymentMethods\Pages;

use App\Filament\Tenant\Resources\PosPaymentMethods\PosPaymentMethodResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePosPaymentMethod extends CreateRecord
{
    protected static string $resource = PosPaymentMethodResource::class;
}
