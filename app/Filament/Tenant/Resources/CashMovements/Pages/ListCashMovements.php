<?php

namespace App\Filament\Tenant\Resources\CashMovements\Pages;

use App\Filament\Tenant\Resources\CashMovements\CashMovementResource;
use Filament\Resources\Pages\ListRecords;

class ListCashMovements extends ListRecords
{
    protected static string $resource = CashMovementResource::class;
}
