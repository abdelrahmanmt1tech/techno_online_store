<?php

namespace Tests\Unit\MetaReset;

use App\Support\MetaReset\MetaIntegrationResetRegistry;
use App\Support\MetaReset\MetaIntegrationResetTable;
use PHPUnit\Framework\TestCase;

class MetaIntegrationResetRegistryTest extends TestCase
{
    public function test_registry_lists_expected_central_and_tenant_tables(): void
    {
        $registry = new MetaIntegrationResetRegistry;
        $tables = collect($registry->all())->map(fn (MetaIntegrationResetTable $t) => $t->table)->all();

        $this->assertContains('whatsapp_number_registry', $tables);
        $this->assertContains('whatsapp_onboarding_sessions', $tables);
        $this->assertContains('whatsapp_webhook_events', $tables);
        $this->assertContains('whatsapp_messages', $tables);
        $this->assertContains('whatsapp_numbers', $tables);
        $this->assertContains('whatsapp_api_requests', $tables);
        $this->assertContains('messenger_page_registry', $tables);
        $this->assertContains('messenger_onboarding_sessions', $tables);
        $this->assertContains('messenger_messages', $tables);
        $this->assertContains('messenger_pages', $tables);
        $this->assertNotContains('meta_integration_reset_runs', $tables);
        $this->assertNotContains('tenants', $tables);
        $this->assertNotContains('products', $tables);
    }

    public function test_scope_filtering_and_priority_order(): void
    {
        $registry = new MetaIntegrationResetRegistry;

        $waTenant = $registry->forScopeAndDatabase('whatsapp', 'tenant');
        $this->assertSame('whatsapp_api_requests', $waTenant[0]->table);
        $this->assertSame('whatsapp_numbers', end($waTenant)->table);
        $this->assertTrue(collect($waTenant)->every(fn ($t) => $t->channel === 'whatsapp'));

        $msCentral = $registry->forScopeAndDatabase('messenger', 'central');
        $this->assertSame('messenger_webhook_events', $msCentral[0]->table);
        $this->assertSame('messenger_page_registry', end($msCentral)->table);
    }

    public function test_invalid_scope_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new MetaIntegrationResetRegistry)->assertValidScope('instagram');
    }
}
