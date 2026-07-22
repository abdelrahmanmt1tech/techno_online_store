<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Seo extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'meta_title',
        'meta_description',
        'keywords',
        'canonical_url',
        'og_image',
    ];

    protected $appends = [
        'og_image_url',
    ];

    public function getOgImageUrlAttribute(): ?string
    {
        return $this->og_image
            ? asset('storage/tenant'.tenant('id').'/'.$this->og_image)
            : null;
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
