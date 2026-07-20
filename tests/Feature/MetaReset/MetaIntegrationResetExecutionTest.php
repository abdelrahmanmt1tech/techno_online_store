<?php

namespace Tests\Feature\MetaReset;

use App\Models\Admin;
use App\Models\MessengerPageRegistry;
use App\Models\MetaIntegrationResetRun;
use App\Models\Tenant;
use App\Models\Tenant\MessengerPage;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\WhatsAppNumberRegistry;
use App\Support\MetaReset\MetaIntegrationResetService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Messenger\MessengerTestCase;

class MetaIntegrationResetExecutionTest extends MessengerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'meta.integration_reset_enabled' => true,
            'app.bypass_permissions' => true,
        ]);
    }

    public function test_all_reset_deletes_both_channels_and_preserves_non_meta(): void
    {
        Http::fake();

        $tenant = $this->createTenantWithDatabase();
        $admin = $this->makeAdmin();

        WhatsAppNumberRegistry::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_whatsapp_number_id' => 1,
            'display_phone_number' => '+20100',
            'phone_number_id' => 'pn-reset-1',
            'whatsapp_business_account_id' => 'waba-1',
            'status' => 'active',
            'is_active' => true,
        ]);

        MessengerPageRegistry::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_messenger_page_id' => 1,
            'page_id' => 'page-reset-1',
            'page_name' => 'Reset Page',
            'status' => 'active',
            'is_active' => true,
        ]);

        $tenant->run(function () {
            WhatsAppNumber::query()->create([
                'display_phone_number' => '+20100',
                'phone_number_id' => 'pn-reset-1',
                'whatsapp_business_account_id' => 'waba-1',
                'access_token' => 'secret-wa-token-should-not-leak',
                'status' => 'active',
                'is_active' => true,
            ]);

            MessengerPage::query()->create([
                'page_id' => 'page-reset-1',
                'page_name' => 'Reset Page',
                'page_access_token' => 'secret-ms-token-should-not-leak',
                'status' => 'active',
                'is_active' => true,
            ]);
        });

        $service = app(MetaIntegrationResetService::class);
        $preview = $service->preview('all', $admin);

        $this->assertGreaterThan(0, $preview['central']['total_rows']);
        $this->assertSame(
            WhatsAppNumberRegistry::query()->count(),
            collect($preview['central']['tables'])->firstWhere('table', 'whatsapp_number_registry')['row_count']
        );

        // Preview must not delete.
        $this->assertSame(1, WhatsAppNumberRegistry::query()->count());

        $result = $service->execute('all', $preview['token'], $service->confirmationPhrase(), $admin);

        $this->assertSame('completed', $result['status']);
        $this->assertSame(0, WhatsAppNumberRegistry::query()->count());
        $this->assertSame(0, MessengerPageRegistry::query()->count());
        $this->assertTrue(Tenant::query()->whereKey($tenant->id)->exists());

        $tenant->run(function () {
            $this->assertSame(0, WhatsAppNumber::withTrashed()->count());
            $this->assertSame(0, MessengerPage::withTrashed()->count());
        });

        $this->assertFalse(tenancy()->initialized);
        $this->assertGreaterThan(0, MetaIntegrationResetRun::query()->count());

        $encoded = json_encode($result);
        $this->assertStringNotContainsString('secret-wa-token-should-not-leak', $encoded);
        $this->assertStringNotContainsString('secret-ms-token-should-not-leak', $encoded);
        Http::assertNothingSent();
    }

    public function test_whatsapp_only_preserves_messenger(): void
    {
        $tenant = $this->createTenantWithDatabase();
        $admin = $this->makeAdmin();

        WhatsAppNumberRegistry::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_whatsapp_number_id' => 1,
            'display_phone_number' => '+20111',
            'phone_number_id' => 'pn-wa-only',
            'whatsapp_business_account_id' => 'waba-2',
            'status' => 'active',
            'is_active' => true,
        ]);

        MessengerPageRegistry::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_messenger_page_id' => 9,
            'page_id' => 'page-keep',
            'page_name' => 'Keep',
            'status' => 'active',
            'is_active' => true,
        ]);

        $service = app(MetaIntegrationResetService::class);
        $preview = $service->preview('whatsapp', $admin);
        $result = $service->execute('whatsapp', $preview['token'], $service->confirmationPhrase(), $admin);

        $this->assertSame('completed', $result['status']);
        $this->assertSame(0, WhatsAppNumberRegistry::query()->count());
        $this->assertSame(1, MessengerPageRegistry::query()->count());
    }

    public function test_messenger_only_preserves_whatsapp(): void
    {
        $tenant = $this->createTenantWithDatabase();
        $admin = $this->makeAdmin();

        WhatsAppNumberRegistry::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_whatsapp_number_id' => 1,
            'display_phone_number' => '+20112',
            'phone_number_id' => 'pn-keep',
            'whatsapp_business_account_id' => 'waba-3',
            'status' => 'active',
            'is_active' => true,
        ]);

        MessengerPageRegistry::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_messenger_page_id' => 9,
            'page_id' => 'page-ms-only',
            'page_name' => 'Gone',
            'status' => 'active',
            'is_active' => true,
        ]);

        $service = app(MetaIntegrationResetService::class);
        $preview = $service->preview('messenger', $admin);
        $result = $service->execute('messenger', $preview['token'], $service->confirmationPhrase(), $admin);

        $this->assertSame('completed', $result['status']);
        $this->assertSame(1, WhatsAppNumberRegistry::query()->count());
        $this->assertSame(0, MessengerPageRegistry::query()->count());
    }

    public function test_second_run_is_safe_zero_rows(): void
    {
        $admin = $this->makeAdmin();
        $service = app(MetaIntegrationResetService::class);

        $preview1 = $service->preview('all', $admin);
        $service->execute('all', $preview1['token'], $service->confirmationPhrase(), $admin);

        $preview2 = $service->preview('all', $admin);
        $result = $service->execute('all', $preview2['token'], $service->confirmationPhrase(), $admin);

        $this->assertSame(0, $result['central_rows_deleted']);
        $this->assertSame(0, $result['tenant_rows_deleted']);
        $this->assertSame('completed', $result['status']);
    }

    public function test_feature_flag_blocks_execution(): void
    {
        config(['meta.integration_reset_enabled' => false]);
        $admin = $this->makeAdmin();

        $this->expectException(\RuntimeException::class);
        app(MetaIntegrationResetService::class)->preview('all', $admin);
    }

    public function test_wrong_confirmation_phrase_rejected(): void
    {
        $admin = $this->makeAdmin();
        $service = app(MetaIntegrationResetService::class);
        $preview = $service->preview('all', $admin);

        $this->expectException(\RuntimeException::class);
        $service->execute('all', $preview['token'], 'wrong phrase', $admin);
    }

    public function test_expired_preview_rejected(): void
    {
        $admin = $this->makeAdmin();
        $service = app(MetaIntegrationResetService::class);
        $preview = $service->preview('all', $admin);

        Cache::put('meta-integration-reset-preview:'.$preview['token'], [
            'scope' => 'all',
            'previewed_at' => now()->subHours(2)->timestamp,
            'tenants_total' => 0,
        ], now()->addMinutes(10));

        $this->expectException(\RuntimeException::class);
        $service->execute('all', $preview['token'], $service->confirmationPhrase(), $admin);
    }

    public function test_central_context_required(): void
    {
        $tenant = $this->createTenantWithDatabase();
        $admin = $this->makeAdmin();
        $service = app(MetaIntegrationResetService::class);

        tenancy()->initialize($tenant);

        try {
            $this->expectException(\RuntimeException::class);
            $service->preview('all', $admin);
        } finally {
            tenancy()->end();
        }
    }

    public function test_audit_survives_reset(): void
    {
        $admin = $this->makeAdmin();
        $service = app(MetaIntegrationResetService::class);
        $preview = $service->preview('all', $admin);
        $service->execute('all', $preview['token'], $service->confirmationPhrase(), $admin);

        $this->assertSame(1, MetaIntegrationResetRun::query()->count());

        $preview2 = $service->preview('all', $admin);
        $service->execute('all', $preview2['token'], $service->confirmationPhrase(), $admin);

        $this->assertSame(2, MetaIntegrationResetRun::query()->count());
        $this->assertTrue(DB::table('meta_integration_reset_runs')->exists());
    }

    protected function makeAdmin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Reset Admin',
            'email' => 'reset-'.str()->uuid().'@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
    }
}
