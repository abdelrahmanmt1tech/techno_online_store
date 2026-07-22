<?php

namespace App\Filament\Tenant\Resources\Warehouses\Pages;

use App\Filament\Tenant\Resources\Warehouses\WarehouseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouse extends CreateRecord
{
    protected static string $resource = WarehouseResource::class;
}
