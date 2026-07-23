<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Page extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'title',
        'slug',
        'image',
        'sort_order',
        'is_active',
        'show_in_header',
        'show_in_footer',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_header' => 'boolean',
            'show_in_footer' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(Seo::class, 'seoable');
    }
}
