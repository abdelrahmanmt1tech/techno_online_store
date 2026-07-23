<?php

namespace App\Models\Tenant;

use App\Enums\Erp\WarehouseType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\Tenant\Concerns\HasErpAuthors;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use BelongsToTenantConnection;
    use HasErpAuthors;
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'warehouse_type',
        'address',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'warehouse_type' => WarehouseType::class,
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }
}
