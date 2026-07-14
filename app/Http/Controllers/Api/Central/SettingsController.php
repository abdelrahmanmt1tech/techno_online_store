<?php

namespace App\Http\Controllers\Api\Central;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ApiResponse;

class SettingsController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $locale = app()->getLocale();
        $termsKey = 'terms_and_conditions_'.$locale;

        $keys = [
            'site_logo',
            'web_favicon',
            'courses_link',
            'contact_us_whatsapp',
            'contact_us_email',
            'contact_us_phone',
        ];

        $settings = Setting::whereIn('key', $keys)->get()->keyBy('key');

        $imageUrl = fn (?string $path) => $path ? asset('storage/'.$path) : null;

        $termsContent = Setting::where('key', $termsKey)->first()?->string_value
            ?? Setting::where('key', 'terms_and_conditions_ar')->first()?->string_value
            ?? '';

        return $this->successResponse([
            'site_logo' => $imageUrl($settings->get('site_logo')?->value),
            'web_favicon' => $imageUrl($settings->get('web_favicon')?->value),
            'courses_link' => $settings->get('courses_link')?->value ?? null,
            'contact_us_whatsapp' => $settings->get('contact_us_whatsapp')?->value ?? null,
            'contact_us_email' => $settings->get('contact_us_email')?->value ?? null,
            'contact_us_phone' => $settings->get('contact_us_phone')?->value ?? null,
            'terms_and_conditions' => $termsContent,
            'app_domain' => config('app.domain'),
            'login_link' => config('app.url').'/tenant-login',
        ]);
    }
}
