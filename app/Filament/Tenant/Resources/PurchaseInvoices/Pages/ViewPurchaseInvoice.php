<?php

namespace App\Filament\Tenant\Resources\PurchaseInvoices\Pages;

use App\Enums\Erp\InvoicePayableType;
use App\Filament\Tenant\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Filament\Tenant\Support\Erp\ErpPaymentActions;
use App\Filament\Tenant\Support\Erp\ErpPrintActions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseInvoice extends ViewRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ErpPrintActions::printPurchaseInvoice(),
            ErpPaymentActions::recordPayment(InvoicePayableType::PurchaseInvoice),
            EditAction::make(),
        ];
    }
}
