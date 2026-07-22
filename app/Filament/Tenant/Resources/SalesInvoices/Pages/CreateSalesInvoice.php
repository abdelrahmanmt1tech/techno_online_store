<?php

namespace App\Filament\Tenant\Resources\SalesInvoices\Pages;

use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\InvoiceStatus;
use App\Filament\Tenant\Resources\SalesInvoices\SalesInvoiceResource;
use App\Services\Erp\DocumentNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesInvoice extends CreateRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['document_number'] = app(DocumentNumberService::class)->next(
            DocumentSequenceType::SalesInvoice,
            $data['branch_id'] ?? null,
        );
        $data['status'] = InvoiceStatus::Draft->value;
        $data['paid_amount'] = $data['paid_amount'] ?? 0;
        $data['due_amount'] = $data['due_amount'] ?? ($data['grand_total'] ?? 0);

        return $data;
    }
}
