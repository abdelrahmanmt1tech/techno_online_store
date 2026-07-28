<?php

namespace App\Filament\Tenant\Resources\Warehouses\Pages;

use App\Filament\Tenant\Resources\Warehouses\WarehouseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => Auth::user()->can('erp.warehouses.manage')),
        ];
    }
}
