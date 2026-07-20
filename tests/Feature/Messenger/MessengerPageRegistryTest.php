<?php

namespace Tests\Feature\Messenger;

use App\Messenger\Enums\MessengerConnectionMethod;
use App\Messenger\Enums\MessengerPageStatus;
use App\Messenger\Enums\MessengerTokenSource;
use App\Models\MessengerPageRegistry;
use App\Models\Tenant;
use App\Models\Tenant\MessengerPage;

class MessengerPageRegistryTest extends MessengerTestCase
{
    protected function createTenantWithDatabase(): Tenant
    {
        $tenant = Tenant::query()->create([
            'id' => (string) str()->uuid(),
            'name' => 'Messenger Store',
            'email' => 'messenger@example.com',
            'is_active' => true,
        ]);

        $tenant->domains()->create(['domain' => 'messenger-'.$tenant->id.'.localhost']);

        return $tenant->fresh();
    }

    public function test_manual_page_defaults_encrypts_token_and_syncs_registry_without_token(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $page = MessengerPage::query()->create([
                'page_id' => 'page-123',
                'page_name' => 'Store Page',
                'page_access_token' => 'secret-page-token',
                'is_default' => true,
                'is_active' => true,
            ]);

            $this->assertSame(MessengerConnectionMethod::Manual, $page->connection_method);
            $this->assertSame(MessengerTokenSource::Manual, $page->token_source);
            $this->assertSame(MessengerPageStatus::Active, $page->status);
            $this->assertNotNull($page->connected_at);
            $this->assertSame('********', $page->masked_page_access_token);
            $this->assertArrayNotHasKey('page_access_token', $page->toArray());

            $raw = $page->getAttributes()['page_access_token'] ?? null;
            $this->assertNotSame('secret-page-token', $raw);
            $this->assertSame('secret-page-token', $page->page_access_token);

            $registry = MessengerPageRegistry::query()->where('page_id', 'page-123')->first();

            $this->assertNotNull($registry);
            $this->assertSame($page->id, $registry->tenant_messenger_page_id);
            $this->assertSame(MessengerConnectionMethod::Manual, $registry->connection_method);
            $this->assertSame(MessengerTokenSource::Manual, $registry->token_source);
            $this->assertArrayNotHasKey('page_access_token', $registry->getAttributes());
            $this->assertFalse(array_key_exists('page_access_token', $registry->toArray()));
        });
    }

    public function test_registry_routes_page_to_owning_tenant_only(): void
    {
        $tenantA = $this->createTenantWithDatabase();
        $tenantB = Tenant::query()->create([
            'id' => (string) str()->uuid(),
            'name' => 'Other Store',
            'email' => 'other@example.com',
            'is_active' => true,
        ]);
        $tenantB->domains()->create(['domain' => 'other-'.$tenantB->id.'.localhost']);

        $tenantA->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-tenant-a',
                'page_name' => 'A Page',
                'page_access_token' => 'token-a',
            ]);
        });

        $registry = MessengerPageRegistry::query()->where('page_id', 'page-tenant-a')->first();

        $this->assertNotNull($registry);
        $this->assertSame($tenantA->id, $registry->tenant_id);
        $this->assertNotSame($tenantB->id, $registry->tenant_id);

        $tenantB->run(function () {
            $this->assertNull(MessengerPage::query()->where('page_id', 'page-tenant-a')->first());
        });
    }
}
