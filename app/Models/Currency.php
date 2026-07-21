<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Currency extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'code',
        'symbol',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
