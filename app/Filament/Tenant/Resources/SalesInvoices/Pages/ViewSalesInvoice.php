<?php

namespace App\Filament\Tenant\Resources\SalesInvoices\Pages;

use App\Enums\Erp\InvoicePayableType;
use App\Filament\Tenant\Resources\SalesInvoices\SalesInvoiceResource;
use App\Filament\Tenant\Support\Erp\ErpPaymentActions;
use App\Filament\Tenant\Support\Erp\ErpPrintActions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesInvoice extends ViewRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ErpPrintActions::printSalesInvoice(),
            ErpPaymentActions::recordPayment(InvoicePayableType::SalesInvoice),
            EditAction::make(),
        ];
    }
}
