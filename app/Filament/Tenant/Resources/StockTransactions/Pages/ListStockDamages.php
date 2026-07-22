<?php

namespace App\Filament\Tenant\Resources\StockTransactions\Pages;

use App\Filament\Tenant\Resources\StockTransactions\StockDamageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockDamages extends ListRecords
{
    protected static string $resource = StockDamageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
