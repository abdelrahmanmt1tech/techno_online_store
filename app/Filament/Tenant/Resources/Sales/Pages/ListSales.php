<?php

namespace App\Filament\Tenant\Resources\Sales\Pages;

use App\Filament\Tenant\Resources\Sales\SaleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()
            ->visible(fn () => Auth::user()->can('erp.sales.manage'))];
    }
}
