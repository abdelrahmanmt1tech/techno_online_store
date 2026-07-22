<?php

namespace App\Filament\Tenant\Resources\StockTransactions\Pages;

use App\Filament\Tenant\Resources\StockTransactions\StockReceiptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockReceipts extends ListRecords
{
    protected static string $resource = StockReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
