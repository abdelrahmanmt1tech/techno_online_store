<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'type',
        'content',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
