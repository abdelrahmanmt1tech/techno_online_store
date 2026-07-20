<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Tests\TestCase;

abstract class WhatsAppTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Tenancy opens extra connections; wrapping the default connection in a
     * transaction breaks tenant DB creation. Remigrate instead (see setUp).
     *
     * @var array<int, string>
     */
    protected $connectionsToTransact = [];

    protected function setUp(): void
    {
        // :memory: PDO is not restored when connectionsToTransact is empty.
        RefreshDatabaseState::$migrated = false;

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
