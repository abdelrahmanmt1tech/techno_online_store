<?php

namespace App\Filament\Tenant\Resources\SalesInvoices\Pages;

use App\Filament\Tenant\Resources\SalesInvoices\SalesInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListSalesInvoices extends ListRecords
{
    protected static string $resource = SalesInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()
            ->visible(fn () => Auth::user()->can('erp.sales_invoices.manage'))];
    }
}
