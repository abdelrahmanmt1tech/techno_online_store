<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Country extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'currency_name'];

    protected $fillable = [
        'name',
        'country_code',
        'currency_name',
        'currency_symbol',
        'currency_code',
        'phone_code',
        'icon',
        'is_active',
        'sort_order',
        'locale',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
