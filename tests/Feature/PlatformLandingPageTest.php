<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePublicCentralDomain;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PlatformLandingPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.public_platform_enforce_central_domain' => true,
            'tenancy.central_domains' => [
                'localhost',
                'online-store.technomasrsystems.com',
            ],
        ]);
    }

    public function test_platform_landing_returns_ok_without_authentication(): void
    {
        $this->assertFalse(tenancy()->initialized);

        $this->get('/platform')
            ->assertOk()
            ->assertSee('Techno Web Masr', false)
            ->assertSee('WhatsApp', false)
            ->assertSee('Messenger', false)
            ->assertSee('https://technomasr.com', false)
            ->assertSee('https://online-store.technomasrsystems.com/privacy-policy', false)
            ->assertSee('https://online-store.technomasrsystems.com/terms-of-service', false)
            ->assertSee('https://online-store.technomasrsystems.com/data-deletion', false)
            ->assertSee('How We Use Meta Platform Data', false);

        $this->assertFalse(tenancy()->initialized);
    }

    public function test_platform_landing_does_not_initialize_tenant_context(): void
    {
        $this->assertFalse(tenancy()->initialized);
        $this->get('/platform')->assertOk();
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_platform_landing_rejects_non_central_domain_when_enforced(): void
    {
        $middleware = new EnsurePublicCentralDomain;

        $this->assertTrue($middleware->isAllowedCentralHost('localhost'));
        $this->assertTrue($middleware->isAllowedCentralHost('online-store.technomasrsystems.com'));
        $this->assertFalse($middleware->isAllowedCentralHost('tenant-example.localhost'));
    }

    public function test_platform_route_is_registered(): void
    {
        $this->assertTrue(Route::has('platform.landing'));
    }
}
