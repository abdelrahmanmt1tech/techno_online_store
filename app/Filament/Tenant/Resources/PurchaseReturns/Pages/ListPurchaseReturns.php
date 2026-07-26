<?php

namespace App\Filament\Tenant\Resources\PurchaseReturns\Pages;

use App\Filament\Tenant\Resources\PurchaseReturns\PurchaseReturnResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListPurchaseReturns extends ListRecords
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()
            ->visible(fn () => Auth::user()->can('erp.purchase_returns.manage'))];
    }
}
