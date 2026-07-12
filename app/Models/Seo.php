<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;

class Seo extends Model
{
    use HasTranslations;

    protected $fillable = [
        'meta_title',
        'meta_description',
        'keywords',
        'canonical_url',
        'og_image',
    ];

    public array $translatable = ['meta_title', 'meta_description', 'keywords'];

    protected $appends = [
        'og_image_url',
    ];

    public function getOgImageUrlAttribute()
    {
        return $this->og_image ? asset('storage/'.$this->og_image) : null;
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function toArray()
    {
        $array = parent::toArray();

        foreach ($this->getTranslatableAttributes() as $attribute) {
            $array[$attribute] = $this->getTranslation($attribute, app()->getLocale());
        }

        return $array;
    }
}
