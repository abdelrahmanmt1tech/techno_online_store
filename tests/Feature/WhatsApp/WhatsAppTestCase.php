<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class WhatsAppTestCase extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.webhook_verify_token' => 'test-verify-token',
            'whatsapp.allow_unsigned_webhooks' => true,
            'whatsapp.app_secret' => null,
        ]);
    }

    protected function createTenantWithDatabase(): Tenant
    {
        $tenant = Tenant::query()->create([
            'id' => (string) str()->uuid(),
            'name' => 'Test Store',
            'email' => 'store@example.com',
            'is_active' => true,
        ]);

        $tenant->domains()->create(['domain' => 'teststore-'.$tenant->id.'.localhost']);

        return $tenant->fresh();
    }
}
