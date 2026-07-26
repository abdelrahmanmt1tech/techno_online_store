<?php

namespace App\Filament\Tenant\Resources\StockTransactions\Pages;

use App\Filament\Tenant\Resources\StockTransactions\StockTransferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListStockTransfers extends ListRecords
{
    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => Auth::user()->can('erp.stock_transfers.manage')),
        ];
    }
}
