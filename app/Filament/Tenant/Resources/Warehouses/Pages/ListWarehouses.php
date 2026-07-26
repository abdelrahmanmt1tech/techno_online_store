<?php

namespace App\Filament\Tenant\Resources\Warehouses\Pages;

use App\Filament\Tenant\Resources\Warehouses\WarehouseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListWarehouses extends ListRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => Auth::user()->can('erp.warehouses.manage')),
        ];
    }
}
