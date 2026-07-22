<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\Tenant\Concerns\HasErpAuthors;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitOfMeasure extends Model
{
    use BelongsToTenantConnection;
    use HasErpAuthors;
    use SoftDeletes;

    protected $table = 'units_of_measure';

    protected $fillable = [
        'name',
        'code',
        'symbol',
        'allows_decimal',
        'precision',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'allows_decimal' => 'boolean',
        'precision' => 'integer',
        'is_active' => 'boolean',
    ];

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'unit_id');
    }
}
