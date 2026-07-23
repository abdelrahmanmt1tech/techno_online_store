<?php

namespace App\Models\Tenant;

use App\Enums\Erp\CommerceSourceType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItemCommerceLink extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'inventory_item_id',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'source_type' => CommerceSourceType::class,
        'source_id' => 'integer',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function source(): ?Model
    {
        return match ($this->source_type) {
            CommerceSourceType::Product => Product::query()->find($this->source_id),
            CommerceSourceType::ProductVariant => ProductVariant::query()->find($this->source_id),
            default => null,
        };
    }
}
