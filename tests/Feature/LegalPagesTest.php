<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_is_public_and_does_not_initialize_tenant(): void
    {
        $this->assertFalse(tenancy()->initialized);

        $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('Privacy Policy', false)
            ->assertSee('Techno Web Masr', false)
            ->assertSee('online-store.technomasrsystems.com', false)
            ->assertSee('support@technowebmasr.com', false);

        $this->assertFalse(tenancy()->initialized);
    }

    public function test_terms_of_service_is_public_and_does_not_initialize_tenant(): void
    {
        $this->assertFalse(tenancy()->initialized);

        $this->get('/terms-of-service')
            ->assertOk()
            ->assertSee('Terms of Service', false)
            ->assertSee('Techno Web Masr', false)
            ->assertSee('Meta Platform Terms', false);

        $this->assertFalse(tenancy()->initialized);
    }

    public function test_data_deletion_is_public_and_does_not_initialize_tenant(): void
    {
        $this->assertFalse(tenancy()->initialized);

        $this->get('/data-deletion')
            ->assertOk()
            ->assertSee('Data Deletion', false)
            ->assertSee('support@technowebmasr.com', false)
            ->assertSee('Facebook Page ID', false);

        $this->assertFalse(tenancy()->initialized);
    }

    public function test_legal_pages_are_accessible_without_authentication(): void
    {
        $this->get('/privacy-policy')->assertOk();
        $this->get('/terms-of-service')->assertOk();
        $this->get('/data-deletion')->assertOk();
    }

    public function test_messenger_onboarding_routes_still_exist(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('messenger.onboarding.start')
        );
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('messenger.onboarding.callback')
        );
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('messenger.onboarding.pages')
        );
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('messenger.onboarding.connect')
        );
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('messenger.onboarding.status')
        );
    }
}
