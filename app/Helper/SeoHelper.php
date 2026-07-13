<?php

use App\Models\Setting;

function buildSeo(string $prefix): array
{
    $seoKeys = [
        $prefix.'_meta_title_ar', $prefix.'_meta_title_en',
        $prefix.'_meta_description_ar', $prefix.'_meta_description_en',
        $prefix.'_keywords_ar', $prefix.'_keywords_en',
        $prefix.'_canonical_url', $prefix.'_og_image',
    ];

    $locale = app()->getLocale();
    $settings = Setting::whereIn('key', $seoKeys)->get()->keyBy('key');

    return [
        'meta_title' => $settings->get($prefix.'_meta_title_'.$locale)?->value,
        'meta_description' => $settings->get($prefix.'_meta_description_'.$locale)?->value,
        'keywords' => $settings->get($prefix.'_keywords_'.$locale)?->value
            ? array_map('trim', explode(' ', $settings->get($prefix.'_keywords_'.$locale)->value))
            : [],
        'canonical_url' => $settings->get($prefix.'_canonical_url')?->value,
        'og_image' => $settings->get($prefix.'_og_image')?->value
            ? asset('storage/'.$settings->get($prefix.'_og_image')->value)
            : null,
    ];
}
