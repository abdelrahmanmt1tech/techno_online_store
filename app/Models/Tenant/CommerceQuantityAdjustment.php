<?php

namespace App\Models\Tenant;

use App\Enums\Erp\CommerceSourceType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommerceQuantityAdjustment extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'source_type',
        'source_id',
        'product_id',
        'product_variant_id',
        'quantity_before',
        'quantity_delta',
        'quantity_after',
        'reason',
        'document_type',
        'document_number',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'created_by',
    ];

    protected $casts = [
        'source_type' => CommerceSourceType::class,
        'source_id' => 'integer',
        'quantity_before' => 'integer',
        'quantity_delta' => 'integer',
        'quantity_after' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
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
