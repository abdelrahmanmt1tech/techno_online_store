<?php

namespace App\Filament\Tenant\Resources\UnitsOfMeasure\Pages;

use App\Filament\Tenant\Resources\UnitsOfMeasure\UnitOfMeasureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUnitOfMeasure extends EditRecord
{
    protected static string $resource = UnitOfMeasureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
