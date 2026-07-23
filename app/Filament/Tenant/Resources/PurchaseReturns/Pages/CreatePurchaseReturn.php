<?php

namespace App\Filament\Tenant\Resources\PurchaseReturns\Pages;

use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Filament\Tenant\Resources\PurchaseReturns\PurchaseReturnResource;
use App\Services\Erp\DocumentNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseReturn extends CreateRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['document_number'] = app(DocumentNumberService::class)->next(
            DocumentSequenceType::PurchaseReturn,
            $data['branch_id'] ?? null,
        );
        $data['status'] = DocumentStatus::Draft->value;

        return $data;
    }
}
