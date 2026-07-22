<?php

namespace App\Filament\Tenant\Resources\PurchaseInvoices\Pages;

use App\Filament\Tenant\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseInvoices extends ListRecords
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
