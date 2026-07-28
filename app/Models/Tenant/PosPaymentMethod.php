<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosPaymentMethod extends Model
{
    use BelongsToTenantConnection;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_active',
        'opens_cash_drawer',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'opens_cash_drawer' => 'boolean',
        'sort_order' => 'integer',
        'meta' => 'array',
    ];
}
