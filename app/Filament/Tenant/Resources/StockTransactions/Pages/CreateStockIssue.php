<?php

namespace App\Filament\Tenant\Resources\StockTransactions\Pages;

use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\StockTransactionType;
use App\Filament\Tenant\Resources\StockTransactions\StockIssueResource;
use App\Services\Erp\DocumentNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreateStockIssue extends CreateRecord
{
    protected static string $resource = StockIssueResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['document_number'] = app(DocumentNumberService::class)->next(
            DocumentSequenceType::StockIssue,
            $data['branch_id'] ?? null,
        );
        $data['status'] = DocumentStatus::Draft->value;
        $data['transaction_type'] = $data['transaction_type'] ?? StockTransactionType::ManualIssue->value;

        return $data;
    }
}
