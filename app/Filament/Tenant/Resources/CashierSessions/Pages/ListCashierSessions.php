<?php

namespace App\Filament\Tenant\Resources\CashierSessions\Pages;

use App\Filament\Tenant\Resources\CashierSessions\CashierSessionResource;
use Filament\Resources\Pages\ListRecords;

class ListCashierSessions extends ListRecords
{
    protected static string $resource = CashierSessionResource::class;
}
