<?php

namespace App\Models\Tenant;

use App\Enums\Commerce\CatalogProductType;
use App\Enums\Commerce\ProductStatus;
use App\Enums\Commerce\ProductVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Shared commerce catalog product — single source of truth for Store, ERP, and POS.
 * Store qty columns remain integer; ERP FIFO stock stays on InventoryItem via commerce links.
 */
class Product extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'brand_id',
        'unit_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'price',
        'sale_price',
        'expense',
        'order',
        'description',
        'notes',
        'meta',
        'quantity',
        'track_stock',
        'disable_orders_for_no_stock',
        'allow_backorders',
        'low_stock_alert',
        'tax_class',
        'weight',
        'length',
        'width',
        'height',
        'dimension_unit',
        'type',
        'catalog_type',
        'link_if_type_digital',
        'is_active',
        'status',
        'visibility',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'expense' => 'decimal:2',
        'track_stock' => 'boolean',
        'disable_orders_for_no_stock' => 'boolean',
        'allow_backorders' => 'boolean',
        'low_stock_alert' => 'decimal:4',
        'weight' => 'decimal:4',
        'length' => 'decimal:4',
        'width' => 'decimal:4',
        'height' => 'decimal:4',
        'is_active' => 'boolean',
        'catalog_type' => CatalogProductType::class,
        'status' => ProductStatus::class,
        'visibility' => ProductVisibility::class,
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if ($product->isDirty('catalog_type') && $product->catalog_type instanceof CatalogProductType) {
                $product->type = $product->catalog_type->legacyStoreType();
            }

            if ($product->isDirty('status') && $product->status instanceof ProductStatus) {
                $product->is_active = $product->status->isSellable();
            } elseif ($product->isDirty('is_active') && ! $product->isDirty('status')) {
                $product->status = $product->is_active
                    ? ProductStatus::Active
                    : ProductStatus::Archived;
            }
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'attribute_product')
            ->withPivot(['attribute_value_id', 'value_text'])
            ->withTimestamps();
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(ProductCode::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(Seo::class, 'seoable');
    }

    public function shouldTrackInventory(): bool
    {
        $type = $this->catalog_type ?? CatalogProductType::fromLegacyStoreType((string) $this->type);

        return $type->tracksInventoryByDefault() && $this->track_stock;
    }
}
