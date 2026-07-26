<?php

namespace App\Filament\Tenant\Resources\StockTransactions\Pages;

use App\Filament\Tenant\Resources\StockTransactions\StockIssueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListStockIssues extends ListRecords
{
    protected static string $resource = StockIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => Auth::user()->can('erp.stock_issues.manage')),
        ];
    }
}
