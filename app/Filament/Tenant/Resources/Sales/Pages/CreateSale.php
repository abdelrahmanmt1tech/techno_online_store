<?php

namespace App\Filament\Tenant\Resources\Sales\Pages;

use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\SaleStatus;
use App\Filament\Tenant\Resources\Sales\SaleResource;
use App\Services\Erp\DocumentNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['document_number'] = app(DocumentNumberService::class)->next(
            DocumentSequenceType::Sale,
            $data['branch_id'] ?? null,
        );
        $data['status'] = SaleStatus::Draft->value;
        $data['subtotal'] = $data['subtotal'] ?? 0;
        $data['discount_total'] = $data['discount_total'] ?? 0;
        $data['tax_total'] = $data['tax_total'] ?? 0;
        $data['grand_total'] = $data['grand_total'] ?? 0;
        $data['cost_total'] = $data['cost_total'] ?? 0;
        $data['profit_total'] = $data['profit_total'] ?? 0;

        return $data;
    }
}
