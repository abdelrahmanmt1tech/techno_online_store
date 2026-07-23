<?php

namespace App\Filament\Tenant\Resources\SalesInvoices\Pages;

use App\Enums\Erp\InvoicePayableType;
use App\Filament\Tenant\Resources\SalesInvoices\SalesInvoiceResource;
use App\Filament\Tenant\Support\Erp\ErpPaymentActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSalesInvoice extends EditRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ErpPaymentActions::recordPayment(InvoicePayableType::SalesInvoice),
            DeleteAction::make()->visible(fn () => ($this->record->status?->value ?? $this->record->status) === 'draft'),
        ];
    }
}
