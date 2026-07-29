<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    use ApiResponse;

    public function __invoke()
    {
        $keys = [
            'site_logo',
            'site_name',
            'site_color',
            'web_favicon',
            'site_font',
            'site_language',
            'site_currency',
            'home_meta_title',
            'home_meta_description',
            'home_keywords',
            'home_canonical_url',
            'home_og_image',
            'custom_head_code',
            'custom_footer_code',
            'footer_logo',
            'footer_description',
            'footer_facebook',
            'footer_instagram',
            'footer_tiktok',
            'footer_youtube',
            'footer_x',
            'footer_linkedin',
            'registration_terms',
        ];

        $settings = Setting::whereIn('key', $keys)->get()->keyBy('key');

        $get = fn (string $key) => $settings->get($key)?->value;

        $getString = fn (string $key) => $settings->get($key)?->string_value;

        $fileUrl = fn (?string $path) => $path
            ? asset('storage/tenant'.tenant('id').'/'.$path)
            : null;

        $currencyCode = $get('site_currency');

        $currency = null;
        if ($currencyCode) {
            $row = DB::connection(
                config('tenancy.database.central_connection', config('database.default'))
            )
                ->table('currencies')
                ->where('code', $currencyCode)
                ->where('is_active', true)
                ->first();

            if ($row) {
                $locale = app()->getLocale();
                $name = json_decode($row->name, true)[$locale] ?? $row->code;
                $currency = [
                    'code' => $row->code,
                    'name' => $name,
                    'symbol' => $row->symbol ?? null,
                ];
            }
        }

        return $this->successResponse([
            'header_logo' => $fileUrl($get('site_logo')),
            'site_name' => $get('site_name'),
            'site_color' => $get('site_color'),
            'web_favicon' => $fileUrl($get('web_favicon')),
            'site_font' => $get('site_font'),
            'site_language' => $get('site_language'),
            'site_currency' => $currency,
            'header_code' => $get('custom_head_code'),
            'footer_code' => $get('custom_footer_code'),
            'footer' => [
                'description' => $getString('footer_description'),
                'logo' => $fileUrl($get('footer_logo')),
                'social' => [
                    'facebook' => $get('footer_facebook'),
                    'instagram' => $get('footer_instagram'),
                    'tiktok' => $get('footer_tiktok'),
                    'youtube' => $get('footer_youtube'),
                    'x' => $get('footer_x'),
                    'linkedin' => $get('footer_linkedin'),
                ],
            ],
            'registration_terms' => $get('registration_terms'),
            'seo' => [
                'meta_title' => $get('home_meta_title'),
                'meta_description' => $get('home_meta_description'),
                'keywords' => $get('home_keywords')
                    ? array_map('trim', explode(' ', $get('home_keywords')))
                    : [],
                'canonical_url' => $get('home_canonical_url'),
                'og_image' => $fileUrl($get('home_og_image')),
            ],
        ]);
    }
}
