<?php

namespace Tests\Feature\Api\Front;

use App\Models\Faq;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('en');

        Setting::create(['key' => 'site_logo', 'value' => 'logos/logo.png']);
        Setting::create(['key' => 'site_name', 'value' => 'Techno Store']);
    }

    public function test_it_returns_home_data_structure(): void
    {
        Setting::create(['key' => 'intro_section_active', 'value' => '1']);

        $response = $this->getJson('/api/home');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'intro',
                    'about',
                    'statistics',
                    'ai_services',
                    'plans',
                    'payment_gateways',
                    'shipping_companies',
                    'marketing_channels',
                    'training_support',
                    'faqs',
                    'have_question',
                    'contact_us',
                    'seo',
                ],
            ]);

        $response->assertJson([
            'success' => true,
            'message' => 'Data fetched successfully.',
        ]);

        $this->assertIsArray($response->json('data.intro'));
        $this->assertNull($response->json('data.about'));
    }

    public function test_it_returns_localized_content_in_arabic(): void
    {
        app()->setLocale('ar');

        Setting::create(['key' => 'intro_title_ar', 'value' => 'عنوان الترحيب بالعربية']);
        Setting::create(['key' => 'intro_title_en', 'value' => 'Welcome Title in English']);
        Setting::create(['key' => 'intro_description_ar', 'value' => 'وصف plain', 'string_value' => 'وصف بالعربية']);
        Setting::create(['key' => 'intro_description_en', 'value' => 'Description plain', 'string_value' => 'Description in English']);
        Setting::create(['key' => 'intro_section_active', 'value' => '1']);

        $response = $this->getJson('/api/home');

        $response->assertOk();
        $response->assertJsonPath('data.intro.title', 'عنوان الترحيب بالعربية');
        $response->assertJsonPath('data.intro.description', 'وصف بالعربية');
    }

    public function test_it_returns_localized_content_in_english(): void
    {
        app()->setLocale('en');

        Setting::create(['key' => 'intro_title_ar', 'value' => 'عنوان الترحيب بالعربية']);
        Setting::create(['key' => 'intro_title_en', 'value' => 'Welcome Title in English']);
        Setting::create(['key' => 'intro_section_active', 'value' => '1']);

        $response = $this->getJson('/api/home');

        $response->assertOk();
        $response->assertJsonPath('data.intro.title', 'Welcome Title in English');
    }

    public function test_it_returns_null_for_inactive_sections(): void
    {
        Setting::create(['key' => 'intro_section_active', 'value' => '0']);
        Setting::create(['key' => 'about_section_active', 'value' => '1']);

        $response = $this->getJson('/api/home');

        $response->assertOk();
        $this->assertNull($response->json('data.intro'));
        $this->assertIsArray($response->json('data.about'));
    }

    public function test_it_returns_rich_editor_content_from_string_value(): void
    {
        app()->setLocale('ar');

        Setting::create([
            'key' => 'intro_description_ar',
            'value' => 'plain text fallback',
            'string_value' => '<p>rich editor content</p>',
        ]);
        Setting::create(['key' => 'intro_section_active', 'value' => '1']);

        $response = $this->getJson('/api/home');

        $response->assertOk();
        $response->assertJsonPath('data.intro.description', '<p>rich editor content</p>');
    }

    public function test_it_decodes_json_fields_correctly(): void
    {
        app()->setLocale('en');

        Setting::create(['key' => 'about_section_active', 'value' => '1']);
        Setting::create([
            'key' => 'about_features',
            'value' => json_encode([
                [
                    'title_ar' => 'ميزة 1',
                    'title_en' => 'Feature 1',
                    'description_ar' => 'وصف الميزة 1',
                    'description_en' => 'Feature 1 description',
                    'image' => 'features/feature1.png',
                ],
            ]),
        ]);
        Setting::create(['key' => 'statistics_section_active', 'value' => '1']);
        Setting::create([
            'key' => 'statistics_items',
            'value' => json_encode([
                [
                    'title_ar' => 'إحصائية 1',
                    'title_en' => 'Stat 1',
                    'value' => 150,
                ],
            ]),
        ]);

        $response = $this->getJson('/api/home');

        $response->assertOk();
        $response->assertJsonPath('data.about.features.0.title', 'Feature 1');
        $response->assertJsonPath('data.about.features.0.description', 'Feature 1 description');
        $response->assertJsonPath('data.statistics.items.0.title', 'Stat 1');
        $response->assertJsonPath('data.statistics.items.0.value', 150);
    }

    public function test_it_returns_only_active_plans_with_features(): void
    {
        app()->setLocale('en');

        Setting::create(['key' => 'intro_section_active', 'value' => '1']);

        $activePlan = Plan::factory()->create(['order' => 1]);
        PlanFeature::factory()->for($activePlan)->create([
            'name' => ['en' => 'Feature A', 'ar' => 'ميزة أ'],
            'order' => 1,
        ]);
        PlanFeature::factory()->for($activePlan)->create([
            'name' => ['en' => 'Feature B', 'ar' => 'ميزة ب'],
            'order' => 2,
        ]);

        Plan::factory()->inactive()->create(['order' => 2]);

        $activePlan2 = Plan::factory()->create(['order' => 0, 'is_active' => true]);
        PlanFeature::factory()->for($activePlan2)->create([
            'name' => ['en' => 'Feature C', 'ar' => 'ميزة ج'],
            'order' => 1,
        ]);

        $response = $this->getJson('/api/home');

        $response->assertOk();
        $plans = $response->json('data.plans.items');
        $this->assertCount(2, $plans);
        $this->assertEquals($activePlan2->id, $plans[0]['id']);
        $this->assertEquals($activePlan->id, $plans[1]['id']);
        $this->assertCount(2, $plans[1]['features']);
        $this->assertEquals('Feature A', $plans[1]['features'][0]['name']);
    }

    public function test_it_returns_only_active_faqs_without_faqable(): void
    {
        Setting::create(['key' => 'intro_section_active', 'value' => '1']);

        $faq1 = Faq::factory()->create(['order' => 1]);
        Faq::factory()->inactive()->create(['order' => 2]);
        Faq::factory()->forModel('App\Models\Plan', 1)->create(['order' => 3]);

        $response = $this->getJson('/api/home');

        $response->assertOk();
        $faqs = $response->json('data.faqs.items');
        $this->assertCount(1, $faqs);
        $this->assertEquals($faq1->id, $faqs[0]['id']);
    }

    public function test_it_generates_correct_image_urls(): void
    {
        Setting::create(['key' => 'intro_section_active', 'value' => '1']);
        Setting::create(['key' => 'intro_image', 'value' => 'images/intro.png']);

        $response = $this->getJson('/api/home');

        $response->assertOk();
        $this->assertStringContainsString(
            '/storage/images/intro.png',
            $response->json('data.intro.image')
        );
    }

    public function test_it_returns_seo_data(): void
    {
        app()->setLocale('en');

        Setting::create(['key' => 'intro_section_active', 'value' => '1']);
        Setting::create(['key' => 'home_meta_title_en', 'value' => 'Home Page Title']);
        Setting::create(['key' => 'home_meta_description_en', 'value' => 'Home page description']);
        Setting::create(['key' => 'home_keywords_en', 'value' => 'store, shop, online']);
        Setting::create(['key' => 'home_canonical_url', 'value' => 'https://example.com']);
        Setting::create(['key' => 'home_og_image', 'value' => 'seo/og-image.png']);

        $response = $this->getJson('/api/home');

        $response->assertOk();
        $response->assertJsonPath('data.seo.meta_title', 'Home Page Title');
        $response->assertJsonPath('data.seo.meta_description', 'Home page description');
        $response->assertJsonPath('data.seo.keywords', ['store,', 'shop,', 'online']);
        $response->assertJsonPath('data.seo.canonical_url', 'https://example.com');
        $this->assertStringContainsString(
            '/storage/seo/og-image.png',
            $response->json('data.seo.og_image')
        );
    }

    public function test_it_handles_empty_settings_gracefully(): void
    {
        Setting::truncate();

        $response = $this->getJson('/api/home');

        $response->assertOk();
        $this->assertNull($response->json('data.intro'));
        $this->assertNull($response->json('data.about'));
        $this->assertNull($response->json('data.statistics'));
        $this->assertNull($response->json('data.ai_services'));
        $this->assertNull($response->json('data.payment_gateways'));
        $this->assertNull($response->json('data.shipping_companies'));
        $this->assertNull($response->json('data.marketing_channels'));
        $this->assertNull($response->json('data.training_support'));
        $this->assertNull($response->json('data.have_question'));
        $this->assertNull($response->json('data.contact_us'));
        $response->assertJsonPath('data.plans.items', []);
        $response->assertJsonPath('data.faqs.items', []);
        $response->assertJsonPath('data.seo.meta_title', null);
        $response->assertJsonPath('data.seo.keywords', []);
        $response->assertJsonPath('data.seo.og_image', null);
    }

    public function test_it_returns_image_as_null_when_path_is_empty(): void
    {
        Setting::create(['key' => 'intro_section_active', 'value' => '1']);
        Setting::create(['key' => 'intro_image', 'value' => '']);

        $response = $this->getJson('/api/home');

        $response->assertOk();
        $response->assertJsonPath('data.intro.image', null);
    }

    public function test_it_returns_marketing_channels_with_icons(): void
    {
        app()->setLocale('en');

        Setting::create(['key' => 'marketing_channels_section_active', 'value' => '1']);
        Setting::create([
            'key' => 'marketing_channels_items',
            'value' => json_encode([
                [
                    'title_ar' => 'قناة 1',
                    'title_en' => 'Channel 1',
                    'description_ar' => 'وصف',
                    'description_en' => 'Description',
                    'icons' => [
                        ['icon' => 'icons/facebook.png'],
                        ['icon' => 'icons/twitter.png'],
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/home');

        $response->assertOk();
        // Section-level link was intentionally removed from the home API / Filament settings.
        $this->assertArrayNotHasKey('link', $response->json('data.marketing_channels'));
        $this->assertArrayNotHasKey('link', $response->json('data.marketing_channels.items.0'));
        $this->assertCount(2, $response->json('data.marketing_channels.items.0.icons'));
        $this->assertStringContainsString(
            '/storage/icons/facebook.png',
            $response->json('data.marketing_channels.items.0.icons.0.icon')
        );
    }

    public function test_it_returns_empty_string_when_locale_specific_key_has_empty_value(): void
    {
        app()->setLocale('en');

        Setting::create(['key' => 'intro_title_ar', 'value' => 'العنوان العربي']);
        Setting::create(['key' => 'intro_title_en', 'value' => '']);
        Setting::create(['key' => 'intro_section_active', 'value' => '1']);

        $response = $this->getJson('/api/home');

        $response->assertOk();
        $response->assertJsonPath('data.intro.title', '');
    }

    public function test_it_returns_footer_data(): void
    {
        app()->setLocale('ar');

        Setting::create(['key' => 'footer_logo', 'value' => 'footer/logo.png']);
        Setting::create(['key' => 'footer_description_ar', 'string_value' => '<p>وصف</p>']);
        Setting::create(['key' => 'footer_description_en', 'string_value' => '<p>Description</p>']);
        Setting::create(['key' => 'footer_facebook', 'value' => 'https://facebook.com/page']);
        Setting::create(['key' => 'footer_instagram', 'value' => 'https://instagram.com/page']);

        $response = $this->getJson('/api/footer');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'logo',
                'description',
                'social' => ['facebook', 'instagram', 'tiktok', 'youtube', 'x', 'linkedin'],
            ],
        ]);
        $response->assertJsonPath('data.description', '<p>وصف</p>');
        $response->assertJsonPath('data.social.facebook', 'https://facebook.com/page');
        $response->assertJsonPath('data.social.instagram', 'https://instagram.com/page');
        $this->assertStringContainsString('/storage/footer/logo.png', $response->json('data.logo'));
        $this->assertNull($response->json('data.social.tiktok'));
    }

    public function test_it_returns_terms_and_conditions(): void
    {
        app()->setLocale('en');

        Setting::create(['key' => 'terms_and_conditions_ar', 'string_value' => '<p>الشروط العربية</p>']);
        Setting::create(['key' => 'terms_and_conditions_en', 'string_value' => '<p>English terms</p>']);

        $response = $this->getJson('/api/terms');

        $response->assertOk();
        $response->assertJsonPath('data.content', '<p>English terms</p>');
    }

    public function test_it_falls_back_to_arabic_terms_when_locale_missing(): void
    {
        app()->setLocale('fr');

        Setting::create(['key' => 'terms_and_conditions_ar', 'string_value' => '<p>الشروط العربية</p>']);

        $response = $this->getJson('/api/terms');

        $response->assertOk();
        $response->assertJsonPath('data.content', '<p>الشروط العربية</p>');
    }

    public function test_it_returns_privacy_policy(): void
    {
        app()->setLocale('en');

        Setting::create(['key' => 'privacy_policy_ar', 'string_value' => '<p>الخصوصية العربية</p>']);
        Setting::create(['key' => 'privacy_policy_en', 'string_value' => '<p>English privacy</p>']);

        $response = $this->getJson('/api/privacy');

        $response->assertOk();
        $response->assertJsonPath('data.content', '<p>English privacy</p>');
    }

    public function test_it_returns_empty_string_when_no_terms_exist(): void
    {
        $response = $this->getJson('/api/terms');

        $response->assertOk();
        $response->assertJsonPath('data.content', '');
    }
}
