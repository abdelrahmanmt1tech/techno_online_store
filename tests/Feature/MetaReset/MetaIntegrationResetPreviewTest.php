<?php

namespace Tests\Feature\MetaReset;

use App\Models\Admin;
use App\Models\WhatsAppNumberRegistry;
use App\Support\MetaReset\MetaIntegrationResetService;
use Tests\Feature\Messenger\MessengerTestCase;

class MetaIntegrationResetPreviewTest extends MessengerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'meta.integration_reset_enabled' => true,
            'app.bypass_permissions' => true,
        ]);
    }

    public function test_preview_is_read_only_and_scoped(): void
    {
        $tenant = $this->createTenantWithDatabase();
        $admin = Admin::query()->create([
            'name' => 'Preview Admin',
            'email' => 'preview-'.str()->uuid().'@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        WhatsAppNumberRegistry::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_whatsapp_number_id' => 1,
            'display_phone_number' => '+20999',
            'phone_number_id' => 'pn-preview',
            'whatsapp_business_account_id' => 'waba-p',
            'status' => 'active',
            'is_active' => true,
        ]);

        $preview = app(MetaIntegrationResetService::class)->preview('whatsapp', $admin);

        $this->assertSame('whatsapp', $preview['scope']);
        $this->assertArrayHasKey('token', $preview);
        $this->assertArrayHasKey('expires_at', $preview);
        $this->assertSame(1, WhatsAppNumberRegistry::query()->count());

        $centralTables = collect($preview['central']['tables'])->pluck('table');
        $this->assertTrue($centralTables->contains('whatsapp_number_registry'));
        $this->assertFalse($centralTables->contains('messenger_page_registry'));

        $this->assertFalse(tenancy()->initialized);
        $this->assertStringContainsString('Local platform records only', $preview['external_note']);
    }

    public function test_empty_state_preview_succeeds(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Empty Admin',
            'email' => 'empty-'.str()->uuid().'@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $preview = app(MetaIntegrationResetService::class)->preview('all', $admin);

        $this->assertSame(0, $preview['central']['total_rows']);
        $this->assertSame(0, $preview['tenants']['total_rows']);
    }
}
