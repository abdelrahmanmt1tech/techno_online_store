<?php

namespace App\Http\Controllers\Api\Central;

use App\Http\Controllers\Controller;
use App\Http\Resources\Central\CategoryResource;
use App\Http\Resources\Central\FaqResource;
use App\Http\Resources\Central\PlanResource;
use App\Http\Resources\Central\ThemeResource;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Theme;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use ApiResponse;

    public function getHomeData()
    {
        $locale = app()->getLocale();

        $settingKeys = [
            'intro_section_active',
            'intro_title_ar', 'intro_title_en',
            'intro_description_ar', 'intro_description_en',
            'intro_image', // 'intro_link',

            'about_section_active',
            'about_small_title_ar', 'about_small_title_en',
            'about_main_title_ar', 'about_main_title_en',
            'about_description_ar', 'about_description_en',
            'about_features',

            'statistics_section_active',
            'statistics_title_ar', 'statistics_title_en',
            'statistics_description_ar', 'statistics_description_en',
            'statistics_items',

            'ai_services_section_active',
            'ai_services_small_title_ar', 'ai_services_small_title_en',
            'ai_services_main_title_ar', 'ai_services_main_title_en',
            'ai_services_description_ar', 'ai_services_description_en',
            'ai_services_items',

            'plans_title_ar', 'plans_title_en',
            'plans_description_ar', 'plans_description_en',

            'payment_gateways_section_active',
            'payment_gateways_small_title_ar', 'payment_gateways_small_title_en',
            'payment_gateways_main_title_ar', 'payment_gateways_main_title_en',
            'payment_gateways_description_ar', 'payment_gateways_description_en',
            'payment_gateways_image',
            'payment_gateways_features',

            'shipping_companies_section_active',
            'shipping_companies_small_title_ar', 'shipping_companies_small_title_en',
            'shipping_companies_main_title_ar', 'shipping_companies_main_title_en',
            'shipping_companies_description_ar', 'shipping_companies_description_en',
            'shipping_companies_image',
            'shipping_companies_features',

            'marketing_channels_section_active',
            'marketing_channels_small_title_ar', 'marketing_channels_small_title_en',
            'marketing_channels_main_title_ar', 'marketing_channels_main_title_en',
            'marketing_channels_description_ar', 'marketing_channels_description_en',
            // 'marketing_channels_link',
            'marketing_channels_items',

            'training_support_section_active',
            'training_support_small_title_ar', 'training_support_small_title_en',
            'training_support_main_title_ar', 'training_support_main_title_en',
            'training_support_description_ar', 'training_support_description_en',
            'training_support_items',

            'faqs_small_title_ar', 'faqs_small_title_en',
            'faqs_main_title_ar', 'faqs_main_title_en',
            'faqs_description_ar', 'faqs_description_en',

            'have_question_section_active',
            'have_question_title_ar', 'have_question_title_en',
            'have_question_description_ar', 'have_question_description_en',
            // 'have_question_link',

            'contact_us_section_active',
            'contact_us_small_title_ar', 'contact_us_small_title_en',
            'contact_us_main_title_ar', 'contact_us_main_title_en',
            'contact_us_description_ar', 'contact_us_description_en',
            'contact_us_image',
            'contact_us_email', 'contact_us_phone', 'contact_us_whatsapp',

            'site_logo', 'site_name',
            'footer_logo',
            'footer_description_ar', 'footer_description_en',
            'footer_facebook', 'footer_instagram', 'footer_tiktok',
            'footer_youtube', 'footer_x', 'footer_linkedin',
        ];

        $richEditorKeys = [
            'intro_description_ar', 'intro_description_en',
            'about_description_ar', 'about_description_en',
            'ai_services_description_ar', 'ai_services_description_en',
            'payment_gateways_description_ar', 'payment_gateways_description_en',
            'shipping_companies_description_ar', 'shipping_companies_description_en',
            'marketing_channels_description_ar', 'marketing_channels_description_en',
            'training_support_description_ar', 'training_support_description_en',
            'have_question_description_ar', 'have_question_description_en',
            'contact_us_description_ar', 'contact_us_description_en',
            'footer_description_ar', 'footer_description_en',
        ];

        $settings = Setting::whereIn('key', $settingKeys)->get()->keyBy('key');

        $getLocaleValue = function (string $prefix) use ($settings, $locale, $richEditorKeys) {
            $localeKey = $prefix.'_'.$locale;

            $setting = $settings->get($localeKey);
            if (! $setting) {
                return '';
            }

            if (in_array($localeKey, $richEditorKeys)) {
                return $setting->string_value ?? '';
            }

            return $setting->value ?? '';
        };

        $getValue = fn (string $key) => $settings->get($key)?->value ?? '';

        $getJsonArray = function (string $key) use ($settings): array {
            $setting = $settings->get($key);
            if (! $setting || ! $setting->value) {
                return [];
            }
            $decoded = json_decode($setting->value, true);

            return is_array($decoded) ? $decoded : [];
        };

        $imageUrl = fn (?string $path) => $path ? asset('storage/'.$path) : null;

        $whenActive = function (string $activeKey, callable $data) use ($getValue): ?array {
            if (! (bool) $getValue($activeKey)) {
                return null;
            }

            return $data();
        };

        $plans = PlanResource::collection(
            Plan::where('is_active', true)
                ->orderBy('order')
                ->with('features')
                ->get()
        );

        $faqs = FaqResource::collection(
            Faq::where('is_active', true)
                ->whereNull('faqable_type')
                ->orderBy('order')
                ->get()
        );

        return $this->successResponse([
            'intro' => $whenActive('intro_section_active', fn () => [
                'title' => $getLocaleValue('intro_title'),
                'description' => $getLocaleValue('intro_description'),
                'image' => $imageUrl($getValue('intro_image')),
                // 'link' => $getValue('intro_link'),
            ]),
            'about' => $whenActive('about_section_active', fn () => [
                'small_title' => $getLocaleValue('about_small_title'),
                'main_title' => $getLocaleValue('about_main_title'),
                'description' => $getLocaleValue('about_description'),
                'features' => collect($getJsonArray('about_features'))->map(fn ($item) => [
                    'title' => $item['title_'.$locale] ?? $item['title_ar'] ?? '',
                    'description' => $item['description_'.$locale] ?? $item['description_ar'] ?? '',
                    'image' => $imageUrl($item['image'] ?? null),
                ]),
            ]),
            'statistics' => $whenActive('statistics_section_active', fn () => [
                'title' => $getLocaleValue('statistics_title'),
                'description' => $getLocaleValue('statistics_description'),
                'items' => collect($getJsonArray('statistics_items'))->map(fn ($item) => [
                    'title' => $item['title_'.$locale] ?? $item['title_ar'] ?? '',
                    'value' => $item['value'] ?? 0,
                ]),
            ]),
            'ai_services' => $whenActive('ai_services_section_active', fn () => [
                'small_title' => $getLocaleValue('ai_services_small_title'),
                'main_title' => $getLocaleValue('ai_services_main_title'),
                'description' => $getLocaleValue('ai_services_description'),
                'items' => collect($getJsonArray('ai_services_items'))->map(fn ($item) => [
                    'title' => $item['title_'.$locale] ?? $item['title_ar'] ?? '',
                    'description' => $item['description_'.$locale] ?? $item['description_ar'] ?? '',
                    'image' => $imageUrl($item['image'] ?? null),
                ]),
            ]),
            'plans' => [
                'title' => $getLocaleValue('plans_title'),
                'description' => $getLocaleValue('plans_description'),
                'items' => $plans,
            ],
            'payment_gateways' => $whenActive('payment_gateways_section_active', fn () => [
                'small_title' => $getLocaleValue('payment_gateways_small_title'),
                'main_title' => $getLocaleValue('payment_gateways_main_title'),
                'description' => $getLocaleValue('payment_gateways_description'),
                'image' => $imageUrl($getValue('payment_gateways_image')),
                'features' => collect($getJsonArray('payment_gateways_features'))->map(fn ($item) => [
                    'title' => $item['title_'.$locale] ?? $item['title_ar'] ?? '',
                ]),
            ]),
            'shipping_companies' => $whenActive('shipping_companies_section_active', fn () => [
                'small_title' => $getLocaleValue('shipping_companies_small_title'),
                'main_title' => $getLocaleValue('shipping_companies_main_title'),
                'description' => $getLocaleValue('shipping_companies_description'),
                'image' => $imageUrl($getValue('shipping_companies_image')),
                'features' => collect($getJsonArray('shipping_companies_features'))->map(fn ($item) => [
                    'title' => $item['title_'.$locale] ?? $item['title_ar'] ?? '',
                ]),
            ]),
            'marketing_channels' => $whenActive('marketing_channels_section_active', fn () => [
                'small_title' => $getLocaleValue('marketing_channels_small_title'),
                'main_title' => $getLocaleValue('marketing_channels_main_title'),
                'description' => $getLocaleValue('marketing_channels_description'),
                // 'link' => $getValue('marketing_channels_link'),
                'items' => collect($getJsonArray('marketing_channels_items'))->map(fn ($item) => [
                    'title' => $item['title_'.$locale] ?? $item['title_ar'] ?? '',
                    'description' => $item['description_'.$locale] ?? $item['description_ar'] ?? '',
                    'icons' => collect($item['icons'] ?? [])->map(fn ($icon) => [
                        'icon' => $imageUrl($icon['icon'] ?? null),
                    ]),
                ]),
            ]),
            'training_support' => $whenActive('training_support_section_active', fn () => [
                'small_title' => $getLocaleValue('training_support_small_title'),
                'main_title' => $getLocaleValue('training_support_main_title'),
                'description' => $getLocaleValue('training_support_description'),
                'items' => collect($getJsonArray('training_support_items'))->map(fn ($item) => [
                    'title' => $item['title_'.$locale] ?? $item['title_ar'] ?? '',
                    'description' => $item['description_'.$locale] ?? $item['description_ar'] ?? '',
                    'image' => $imageUrl($item['image'] ?? null),
                ]),
            ]),
            'faqs' => [
                'small_title' => $getLocaleValue('faqs_small_title'),
                'main_title' => $getLocaleValue('faqs_main_title'),
                'description' => $getLocaleValue('faqs_description'),
                'items' => $faqs,
            ],
            'have_question' => $whenActive('have_question_section_active', fn () => [
                'title' => $getLocaleValue('have_question_title'),
                'description' => $getLocaleValue('have_question_description'),
                // 'link' => $getValue('have_question_link'),
            ]),
            'contact_us' => $whenActive('contact_us_section_active', fn () => [
                'small_title' => $getLocaleValue('contact_us_small_title'),
                'main_title' => $getLocaleValue('contact_us_main_title'),
                'description' => $getLocaleValue('contact_us_description'),
                'image' => $imageUrl($getValue('contact_us_image')),
                'email' => $getValue('contact_us_email'),
                'phone' => $getValue('contact_us_phone'),
                'whatsapp' => $getValue('contact_us_whatsapp'),
            ]),
            'seo' => buildSeo('home'),
        ], __('messages.fetched_successfully'));
    }

    public function getThemes(Request $request)
    {
        $query = Theme::where('is_active', true)
            ->with('categories')
            ->orderBy('order');

        if ($request->filled('category_id')) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $request->category_id));
        }

        if ($request->boolean('featured')) {
            $query->where('featured', true);
        }

        $perPage = min((int) ($request->per_page ?? 12), 50);
        $themes = $query->paginate($perPage);

        $locale = app()->getLocale();
        $extra = [
            'section_title' => Setting::where('key', 'themes_title_'.$locale)->first()?->value ?? '',
        ];

        return $this->paginatedWithExtraResponse(
            $themes,
            ThemeResource::collection($themes),
            $extra,
        );
    }

    public function getCategories()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('order')
            ->get();

        return $this->successResponse(
            CategoryResource::collection($categories),
            __('messages.fetched_successfully'),
        );
    }

    public function getFooter()
    {
        $locale = app()->getLocale();

        $settings = Setting::whereIn('key', [
            'footer_logo',
            'footer_description_ar', 'footer_description_en',
            'footer_facebook', 'footer_instagram', 'footer_tiktok',
            'footer_youtube', 'footer_x', 'footer_linkedin', 'footer_color',
        ])->get()->keyBy('key');

        $getValue = fn (string $key) => $settings->get($key)?->value ?? '';

        $richEditorKey = 'footer_description_'.$locale;
        $description = $settings->get($richEditorKey)?->string_value
            ?? $settings->get('footer_description_ar')?->string_value
            ?? '';

        $logo = $getValue('footer_logo');

        $getNullable = fn (string $key) => $settings->get($key)?->value ?: null;

        return $this->successResponse([
            'logo' => $logo ? asset('storage/'.$logo) : null,
            'description' => $description,
            'footer_color' => $getNullable('footer_color'),
            'social' => [
                'facebook' => $getNullable('footer_facebook'),
                'instagram' => $getNullable('footer_instagram'),
                'tiktok' => $getNullable('footer_tiktok'),
                'youtube' => $getNullable('footer_youtube'),
                'x' => $getNullable('footer_x'),
                'linkedin' => $getNullable('footer_linkedin'),
            ],
        ], __('messages.fetched_successfully'));
    }

    public function getTerms()
    {
        $locale = app()->getLocale();
        $key = 'terms_and_conditions_'.$locale;

        $content = Setting::where('key', $key)->first()?->string_value
            ?? Setting::where('key', 'terms_and_conditions_ar')->first()?->string_value
            ?? '';

        return $this->successResponse(['content' => $content], __('messages.fetched_successfully'));
    }

    public function getPrivacy()
    {
        $locale = app()->getLocale();
        $key = 'privacy_policy_'.$locale;

        $content = Setting::where('key', $key)->first()?->string_value
            ?? Setting::where('key', 'privacy_policy_ar')->first()?->string_value
            ?? '';

        return $this->successResponse(['content' => $content], __('messages.fetched_successfully'));
    }
}
