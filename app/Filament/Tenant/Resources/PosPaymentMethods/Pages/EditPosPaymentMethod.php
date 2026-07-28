<?php

namespace App\Filament\Tenant\Resources\PosPaymentMethods\Pages;

use App\Filament\Tenant\Resources\PosPaymentMethods\PosPaymentMethodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPosPaymentMethod extends EditRecord
{
    protected static string $resource = PosPaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
