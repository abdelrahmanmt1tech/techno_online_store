<?php

namespace App\Filament\Tenant\Resources\SalesReturns\Pages;

use App\Filament\Tenant\Resources\SalesReturns\SalesReturnResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListSalesReturns extends ListRecords
{
    protected static string $resource = SalesReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()
            ->visible(fn () => Auth::user()->can('erp.sales_returns.manage'))];
    }
}
