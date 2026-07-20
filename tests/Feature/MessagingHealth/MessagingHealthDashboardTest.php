<?php

namespace Tests\Feature\MessagingHealth;

use App\Filament\Pages\MessagingHealthDashboard;
use App\Messenger\Enums\MessengerConnectionMethod;
use App\Messenger\Enums\MessengerPageStatus;
use App\Messenger\Enums\MessengerWebhookProcessingStatus;
use App\Models\Admin;
use App\Models\MessengerPageRegistry;
use App\Models\MessengerWebhookEvent;
use App\Models\Tenant;
use App\Models\Tenant\MessengerPage;
use App\Models\WhatsAppNumberRegistry;
use App\Models\WhatsAppWebhookEvent;
use App\Support\MessagingHealth\InspectTenantMessagingConnectionAction;
use App\Support\MessagingHealth\MessagingHealthSummaryService;
use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;
use App\WhatsApp\Enums\WhatsAppWebhookProcessingStatus;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Feature\Messenger\MessengerTestCase;

class MessagingHealthDashboardTest extends MessengerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.bypass_permissions' => true]);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/admin/messaging-health')->assertRedirect();
    }

    public function test_admin_can_access_dashboard_and_sees_summary_without_tokens(): void
    {
        Http::fake();

        $tenant = $this->createTenant();

        WhatsAppNumberRegistry::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_whatsapp_number_id' => 1,
            'display_phone_number' => '+201000000001',
            'phone_number_id' => 'wa-phone-1',
            'whatsapp_business_account_id' => 'waba-1',
            'connection_method' => WhatsAppConnectionMethod::ManualApiOnly,
            'onboarding_status' => WhatsAppOnboardingStatus::Completed,
            'status' => WhatsAppConnectionStatus::Active,
            'webhook_status' => 'subscribed',
            'is_active' => true,
        ]);

        MessengerPageRegistry::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_messenger_page_id' => 1,
            'page_id' => 'page-health-1',
            'page_name' => 'Health Page',
            'connection_method' => MessengerConnectionMethod::Manual,
            'status' => MessengerPageStatus::Active,
            'webhook_status' => 'pending',
            'is_active' => true,
        ]);

        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(MessagingHealthDashboard::class)
            ->assertSuccessful()
            ->assertSee(__('dashboard.messaging_health'))
            ->assertSee(__('dashboard.messaging_health_whatsapp_summary'))
            ->assertSee(__('dashboard.messaging_health_messenger_summary'))
            ->assertDontSee('access_token')
            ->assertDontSee('page_access_token');

        Http::assertNothingSent();
    }

    public function test_summary_counts_use_central_registry_only(): void
    {
        $tenant = $this->createTenant();

        WhatsAppNumberRegistry::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_whatsapp_number_id' => 1,
            'display_phone_number' => '+20111',
            'phone_number_id' => 'wa-2',
            'whatsapp_business_account_id' => 'waba-2',
            'connection_method' => WhatsAppConnectionMethod::EmbeddedSignupApiOnly,
            'onboarding_status' => WhatsAppOnboardingStatus::InProgress,
            'status' => WhatsAppConnectionStatus::Active,
            'webhook_status' => 'pending',
            'is_active' => true,
        ]);

        MessengerPageRegistry::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_messenger_page_id' => 2,
            'page_id' => 'page-2',
            'page_name' => 'Page 2',
            'connection_method' => MessengerConnectionMethod::FacebookLogin,
            'status' => MessengerPageStatus::ReconnectRequired,
            'webhook_status' => 'failed',
            'is_active' => true,
        ]);

        $summary = app(MessagingHealthSummaryService::class)->summarize(24);

        $this->assertSame(1, $summary['whatsapp']['total']);
        $this->assertSame(1, $summary['whatsapp']['pending_onboarding']);
        $this->assertSame(1, $summary['whatsapp']['method_api_only']);
        $this->assertSame(1, $summary['messenger']['total']);
        $this->assertSame(1, $summary['messenger']['reconnect_required']);
        $this->assertSame(1, $summary['messenger']['method_facebook_login']);
        $this->assertSame(1, $summary['tenants']['tenants_with_messaging']);
        $this->assertSame(1, $summary['tenants']['both']);
        $this->assertGreaterThanOrEqual(1, $summary['attention_count']);
    }

    public function test_webhook_aggregates_for_periods(): void
    {
        MessengerWebhookEvent::query()->create([
            'provider' => 'meta',
            'event_type' => 'messages',
            'processing_status' => MessengerWebhookProcessingStatus::Processed,
            'page_id' => 'page-agg',
            'processed_at' => now(),
            'created_at' => now()->subHours(2),
            'updated_at' => now(),
        ]);

        $oldFailed = MessengerWebhookEvent::query()->create([
            'provider' => 'meta',
            'event_type' => 'messages',
            'processing_status' => MessengerWebhookProcessingStatus::Failed,
            'page_id' => 'page-agg',
        ]);
        MessengerWebhookEvent::query()->whereKey($oldFailed->id)->update([
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        WhatsAppWebhookEvent::query()->create([
            'provider' => 'meta',
            'event_type' => 'messages',
            'processing_status' => WhatsAppWebhookProcessingStatus::Unresolved,
            'phone_number_id' => 'wa-agg',
            'created_at' => now()->subHours(1),
            'updated_at' => now(),
        ]);

        $day = app(MessagingHealthSummaryService::class)->webhookAggregates(24);
        $this->assertSame(1, $day['messenger']['processed']);
        $this->assertSame(0, $day['messenger']['failed']);
        $this->assertSame(1, $day['whatsapp']['unresolved']);

        $month = app(MessagingHealthSummaryService::class)->webhookAggregates(720);
        $this->assertSame(1, $month['messenger']['failed']);
    }

    public function test_attention_filtering_and_empty_state(): void
    {
        $service = app(MessagingHealthSummaryService::class);

        $this->assertCount(0, $service->attentionRows(['needs_attention_only' => true]));

        $tenant = $this->createTenant();

        WhatsAppNumberRegistry::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_whatsapp_number_id' => 9,
            'display_phone_number' => '+20999',
            'phone_number_id' => 'wa-healthy',
            'whatsapp_business_account_id' => 'waba-h',
            'connection_method' => WhatsAppConnectionMethod::ManualApiOnly,
            'onboarding_status' => WhatsAppOnboardingStatus::Completed,
            'status' => WhatsAppConnectionStatus::Active,
            'webhook_status' => 'subscribed',
            'is_active' => true,
        ]);

        $attention = $service->attentionRows(['needs_attention_only' => true]);
        $this->assertCount(0, $attention);

        $all = $service->attentionRows(['needs_attention_only' => false, 'channel' => 'whatsapp']);
        $this->assertCount(1, $all);
        $this->assertSame('healthy', $all->first()['health']);
        $this->assertStringContainsString('*', $all->first()['asset_id']);
    }

    public function test_tenant_inspection_masks_token_and_ends_context(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-inspect-1',
                'page_name' => 'Inspect Page',
                'page_access_token' => 'super-secret-page-token',
                'status' => MessengerPageStatus::Active,
                'webhook_status' => 'subscribed',
                'is_active' => true,
            ]);
        });

        $registry = MessengerPageRegistry::query()->where('page_id', 'page-inspect-1')->first();
        $this->assertNotNull($registry);

        $result = app(InspectTenantMessagingConnectionAction::class)
            ->execute('messenger', $registry->id);

        $this->assertTrue($result['tenant_connection']['inspected']);
        $this->assertTrue($result['tenant_connection']['token_configured']);
        $this->assertFalse(tenancy()->initialized);

        $encoded = json_encode($result);
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('super-secret-page-token', $encoded);
        $this->assertArrayNotHasKey('conversations', $result);
        $this->assertArrayNotHasKey('messages', $result);
    }

    public function test_dashboard_livewire_inspect_action(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-lw-1',
                'page_name' => 'LW Page',
                'page_access_token' => 'hidden-token-value',
                'status' => MessengerPageStatus::Active,
                'webhook_status' => 'subscribed',
                'is_active' => true,
            ]);
        });

        $registry = MessengerPageRegistry::query()->where('page_id', 'page-lw-1')->firstOrFail();

        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(MessagingHealthDashboard::class)
            ->call('inspectConnection', 'messenger', $registry->id)
            ->assertSet('inspection.tenant_connection.token_configured', true)
            ->assertDontSee('hidden-token-value');

        $this->assertFalse(tenancy()->initialized);
    }

    protected function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'id' => (string) str()->uuid(),
            'name' => 'Health Store',
            'email' => 'health-'.str()->uuid().'@example.com',
            'is_active' => true,
        ]);
    }

    protected function createAdmin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Health Admin',
            'email' => 'health-admin-'.str()->uuid().'@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
    }
}
