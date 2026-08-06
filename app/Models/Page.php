<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasTranslations;

    public const PROTECTED_SLUGS = [
        'privacy-policy',
        'terms-and-conditions',
    ];

    public array $translatable = ['title', 'content'];

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
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'show_in_header' => 'boolean',
            'show_in_footer' => 'boolean',
        ];
    }

    public function isProtected(): bool
    {
        return in_array($this->slug, self::PROTECTED_SLUGS, true);
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(Seo::class, 'seoable');
    }

    protected static function booted(): void
    {
        static::deleting(function (Page $page) {
            if ($page->isProtected()) {
                abort(403, __('dashboard.protected_page_cannot_delete'));
            }
        });
    }
}
