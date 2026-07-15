<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Governorate extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'shipping_cost',
        'is_active',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
