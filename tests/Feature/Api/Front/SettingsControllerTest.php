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
        Setting::create(['key' => 'header_color', 'value' => '#1a1a2e']);
        Setting::create(['key' => 'footer_color', 'value' => '#0f3460']);
        Setting::create(['key' => 'contact_us_whatsapp', 'value' => '966500000000']);

        $response = $this->getJson('/api/settings');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'data' => [
                'site_logo',
                'web_favicon',
                'courses_link',
                'header_color',
                'footer_color',
                'contact_us_whatsapp',
            ],
        ]);
        $response->assertJsonPath('data.courses_link', 'https://example.com/courses');
        $response->assertJsonPath('data.header_color', '#1a1a2e');
        $response->assertJsonPath('data.footer_color', '#0f3460');
        $response->assertJsonPath('data.contact_us_whatsapp', '966500000000');
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
        $response->assertJsonPath('data.header_color', null);
        $response->assertJsonPath('data.footer_color', null);
        $response->assertJsonPath('data.contact_us_whatsapp', null);
    }
}
