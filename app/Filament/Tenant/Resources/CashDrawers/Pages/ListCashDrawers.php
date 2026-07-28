<?php

namespace App\Filament\Tenant\Resources\CashDrawers\Pages;

use App\Filament\Tenant\Resources\CashDrawers\CashDrawerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashDrawers extends ListRecords
{
    protected static string $resource = CashDrawerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
