<?php

namespace App\Filament\Tenant\Resources\PurchaseOrders\Pages;

use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\PurchaseOrderStatus;
use App\Filament\Tenant\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Services\Erp\DocumentNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['document_number'] = app(DocumentNumberService::class)->next(
            DocumentSequenceType::PurchaseOrder,
            $data['branch_id'] ?? null,
        );
        $data['status'] = PurchaseOrderStatus::Draft->value;
        $data['subtotal'] = $data['subtotal'] ?? 0;
        $data['discount_total'] = $data['discount_total'] ?? 0;
        $data['tax_total'] = $data['tax_total'] ?? 0;
        $data['grand_total'] = $data['grand_total'] ?? 0;

        return $data;
    }
}
