<?php

namespace App\Filament\Tenant\Resources\SalesReturns\Pages;

use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Filament\Tenant\Resources\SalesReturns\SalesReturnResource;
use App\Services\Erp\DocumentNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesReturn extends CreateRecord
{
    protected static string $resource = SalesReturnResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['document_number'] = app(DocumentNumberService::class)->next(
            DocumentSequenceType::SalesReturn,
            $data['branch_id'] ?? null,
        );
        $data['status'] = DocumentStatus::Draft->value;

        return $data;
    }
}
