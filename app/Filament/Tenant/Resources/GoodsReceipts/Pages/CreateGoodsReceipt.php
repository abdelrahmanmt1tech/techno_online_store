<?php

namespace App\Filament\Tenant\Resources\GoodsReceipts\Pages;

use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Filament\Tenant\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Services\Erp\DocumentNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreateGoodsReceipt extends CreateRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['document_number'] = app(DocumentNumberService::class)->next(
            DocumentSequenceType::GoodsReceipt,
            $data['branch_id'] ?? null,
        );
        $data['status'] = DocumentStatus::Draft->value;

        return $data;
    }
}
