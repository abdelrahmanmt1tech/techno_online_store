<?php

namespace Tests\Feature\Api\Front;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_all_settings(): void
    {
        Setting::create(['key' => 'site_logo', 'value' => 'general/logo.png']);
        Setting::create(['key' => 'web_favicon', 'value' => 'general/favicon.ico']);
        Setting::create(['key' => 'courses_link', 'value' => 'https://example.com/courses']);
        Setting::create(['key' => 'contact_us_whatsapp', 'value' => '966500000000']);
        Setting::create(['key' => 'contact_us_email', 'value' => 'info@example.com']);
        Setting::create(['key' => 'contact_us_phone', 'value' => '+966500000000']);
        Setting::create(['key' => 'terms_and_conditions_ar', 'string_value' => '<p>الشروط العربية</p>']);
        Setting::create(['key' => 'terms_and_conditions_en', 'string_value' => '<p>English terms</p>']);

        $response = $this->getJson('/api/settings');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'data' => [
                'site_logo',
                'web_favicon',
                'courses_link',
                'contact_us_whatsapp',
                'contact_us_email',
                'contact_us_phone',
                'terms_and_conditions',
                'app_domain',
                'login_link',
            ],
        ]);
        $response->assertJsonPath('data.courses_link', 'https://example.com/courses');
        $response->assertJsonPath('data.contact_us_whatsapp', '966500000000');
        $response->assertJsonPath('data.contact_us_email', 'info@example.com');
        $response->assertJsonPath('data.contact_us_phone', '+966500000000');
        $response->assertJsonPath('data.terms_and_conditions', '<p>الشروط العربية</p>');
        $this->assertStringContainsString('/storage/general/logo.png', $response->json('data.site_logo'));
        $this->assertStringContainsString('/storage/general/favicon.ico', $response->json('data.web_favicon'));
    }

    public function test_it_returns_null_for_missing_settings(): void
    {
        $response = $this->getJson('/api/settings');

        $response->assertOk();
        $response->assertJsonPath('data.site_logo', null);
        $response->assertJsonPath('data.web_favicon', null);
        $response->assertJsonPath('data.courses_link', null);
        $response->assertJsonPath('data.contact_us_whatsapp', null);
        $response->assertJsonPath('data.contact_us_email', null);
        $response->assertJsonPath('data.contact_us_phone', null);
        $response->assertJsonPath('data.terms_and_conditions', '');
    }
}
