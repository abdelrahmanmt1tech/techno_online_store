<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\HomeSectionResolver;
use App\Traits\ApiResponse;

class HomeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private HomeSectionResolver $resolver,
    ) {}

    public function __invoke()
    {
        $sections = $this->resolver->resolve();

        $seoKeys = [
            'home_meta_title',
            'home_meta_description',
            'home_keywords',
            'home_canonical_url',
            'home_og_image',
        ];

        $settings = Setting::whereIn('key', $seoKeys)->get()->keyBy('key');

        $get = fn (string $key) => $settings->get($key)?->value;

        $seo = [
            'meta_title' => $get('home_meta_title'),
            'meta_description' => $get('home_meta_description'),
            'keywords' => $get('home_keywords')
                ? array_map('trim', explode(' ', $get('home_keywords')))
                : [],
            'canonical_url' => $get('home_canonical_url'),
            'og_image' => $get('home_og_image')
                ? asset('storage/tenant'.tenant('id').'/'.$get('home_og_image'))
                : null,
        ];

        return $this->successResponse([
            'sections' => $sections,
            'seo' => $seo,
        ]);
    }
}
