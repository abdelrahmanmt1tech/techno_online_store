<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ApiResponse;

class SettingsController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $keys = [
            'site_logo',
            'web_favicon',
            'courses_link',
            'header_color',
            'footer_color',
            'contact_us_whatsapp',
        ];

        $settings = Setting::whereIn('key', $keys)->get()->keyBy('key');

        $imageUrl = fn (?string $path) => $path ? asset('storage/'.$path) : null;

        return $this->successResponse([
            'site_logo' => $imageUrl($settings->get('site_logo')?->value),
            'web_favicon' => $imageUrl($settings->get('web_favicon')?->value),
            'courses_link' => $settings->get('courses_link')?->value ?? null,
            'header_color' => $settings->get('header_color')?->value ?? null,
            'footer_color' => $settings->get('footer_color')?->value ?? null,
            'contact_us_whatsapp' => $settings->get('contact_us_whatsapp')?->value ?? null,
        ]);
    }
}
