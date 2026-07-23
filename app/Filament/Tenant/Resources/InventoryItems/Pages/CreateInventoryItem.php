<?php

namespace App\Filament\Tenant\Resources\InventoryItems\Pages;

use App\Enums\Erp\CommerceSourceType;
use App\Filament\Tenant\Resources\InventoryItems\InventoryItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryItem extends CreateRecord
{
    protected static string $resource = InventoryItemResource::class;

    /** @var array<string, mixed> */
    public array $commerceLinkData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->commerceLinkData = [
            'product_id' => $data['product_id'] ?? null,
            'product_variant_id' => $data['product_variant_id'] ?? null,
        ];

        unset($data['product_id'], $data['product_variant_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $productVariantId = $this->commerceLinkData['product_variant_id'] ?? null;
        $productId = $this->commerceLinkData['product_id'] ?? null;

        if ($productVariantId) {
            $this->record->commerceLink()->create([
                'source_type' => CommerceSourceType::ProductVariant->value,
                'source_id' => $productVariantId,
            ]);
        } elseif ($productId) {
            $this->record->commerceLink()->create([
                'source_type' => CommerceSourceType::Product->value,
                'source_id' => $productId,
            ]);
        }
    }
}
