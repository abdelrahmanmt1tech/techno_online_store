<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ApiResponse;

class FooterController extends Controller
{
    use ApiResponse;

    public function __invoke()
    {
        $keys = [
            'custom_footer_code',
            'footer_description', 'footer_logo',
            'footer_facebook', 'footer_instagram', 'footer_tiktok',
            'footer_youtube', 'footer_x', 'footer_linkedin',
        ];

        $settings = Setting::whereIn('key', $keys)->get()->keyBy('key');

        $get = fn (string $key) => $settings->get($key)?->value;

        $getString = fn (string $key) => $settings->get($key)?->string_value;

        $fileUrl = fn (?string $path) => $path
            ? asset('storage/tenant'.tenant('id').'/'.$path)
            : null;

        return $this->successResponse([
            'footer_code' => $get('custom_footer_code'),
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
        ]);
    }
}
