<?php

namespace App\Filament\Tenant\Resources\UnitsOfMeasure\Pages;

use App\Filament\Tenant\Resources\UnitsOfMeasure\UnitOfMeasureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUnitOfMeasure extends EditRecord
{
    protected static string $resource = UnitOfMeasureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => Auth::user()->can('erp.uom.manage')),
        ];
    }
}
