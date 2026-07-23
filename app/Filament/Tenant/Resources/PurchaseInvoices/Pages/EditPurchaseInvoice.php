<?php

namespace App\Filament\Tenant\Resources\PurchaseInvoices\Pages;

use App\Enums\Erp\InvoicePayableType;
use App\Filament\Tenant\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Filament\Tenant\Support\Erp\ErpPaymentActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseInvoice extends EditRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ErpPaymentActions::recordPayment(InvoicePayableType::PurchaseInvoice),
            DeleteAction::make()->visible(fn () => ($this->record->status?->value ?? $this->record->status) === 'draft'),
        ];
    }
}
