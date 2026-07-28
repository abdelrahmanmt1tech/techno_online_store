<?php

namespace App\Filament\Tenant\Resources\PosPaymentMethods\Pages;

use App\Filament\Tenant\Resources\PosPaymentMethods\PosPaymentMethodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPosPaymentMethods extends ListRecords
{
    protected static string $resource = PosPaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
