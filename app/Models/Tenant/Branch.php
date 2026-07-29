<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\Tenant\Concerns\HasErpAuthors;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use BelongsToTenantConnection;
    use HasErpAuthors;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'code',
        'phone',
        'email',
        'address',
        'city',
        'latitude',
        'longitude',
        'notes',
        'is_main',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(TenantUser::class, 'branch_user', 'branch_id', 'user_id')
            ->withTimestamps();
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }
}
