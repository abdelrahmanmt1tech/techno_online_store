<?php

namespace Tests\Feature\Messenger;

use App\Filament\Pages\MessengerInboxPage;
use App\Filament\Resources\MessengerPages\MessengerPageResource;
use App\Filament\Resources\MessengerWebhookEvents\MessengerWebhookEventResource;
use App\Filament\Shared\Messenger\Concerns\InteractsWithMessengerInbox;
use App\Messenger\Enums\MessengerWebhookProcessingStatus;
use App\Models\Admin;
use App\Models\MessengerPageRegistry;
use App\Models\MessengerWebhookEvent;
use App\Models\Tenant\MessengerConversation;
use App\Models\Tenant\MessengerMessage;
use App\Models\Tenant\MessengerPage;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use ReflectionClass;
use Spatie\Permission\Models\Permission;

class AdminMessengerUiTest extends MessengerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('messenger.platform.view_all_pages', 'admin');
        Permission::findOrCreate('messenger.platform.manage_all_pages', 'admin');
        Permission::findOrCreate('messenger.platform.view_webhook_events', 'admin');
        Permission::findOrCreate('messenger.platform.troubleshoot', 'admin');
    }

    public function test_admin_registry_resource_never_exposes_tokens(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-admin-registry',
                'page_name' => 'Admin Registry Page',
                'page_access_token' => 'secret-should-not-appear',
            ]);
        });

        $registry = MessengerPageRegistry::query()->where('page_id', 'page-admin-registry')->first();
        $this->assertNotNull($registry);
        $this->assertArrayNotHasKey('page_access_token', $registry->getAttributes());
        $this->assertFalse(array_key_exists('page_access_token', $registry->toArray()));

        $this->assertSame(MessengerPageRegistry::class, MessengerPageResource::getModel());
        $this->assertFalse(MessengerPageResource::canCreate());
        $this->assertFalse(MessengerPageResource::canDelete($registry));
    }

    public function test_admin_webhook_resource_is_read_only_and_filterable_by_tenant(): void
    {
        $tenantA = $this->createTenantWithDatabase();
        $tenantB = $this->createTenantWithDatabase();

        MessengerWebhookEvent::query()->create([
            'provider' => 'meta',
            'event_type' => 'page',
            'summary' => 'Admin Event A',
            'page_id' => 'page-a',
            'tenant_id' => $tenantA->id,
            'processing_status' => MessengerWebhookProcessingStatus::Failed,
            'signature_valid' => true,
            'payload' => ['entry' => [['id' => 'page-a']]],
            'original_payload' => ['entry' => [['id' => 'page-a']]],
        ]);

        MessengerWebhookEvent::query()->create([
            'provider' => 'meta',
            'event_type' => 'page',
            'summary' => 'Admin Event B',
            'page_id' => 'page-b',
            'tenant_id' => $tenantB->id,
            'processing_status' => MessengerWebhookProcessingStatus::Processed,
            'signature_valid' => false,
            'payload' => ['entry' => [['id' => 'page-b']]],
            'original_payload' => ['entry' => [['id' => 'page-b']]],
        ]);

        $this->assertFalse(MessengerWebhookEventResource::canCreate());
        $event = MessengerWebhookEvent::query()->first();
        $this->assertFalse(MessengerWebhookEventResource::canEdit($event));
        $this->assertFalse(MessengerWebhookEventResource::canDelete($event));

        $filtered = MessengerWebhookEventResource::getEloquentQuery()
            ->where('tenant_id', $tenantA->id)
            ->get();

        $this->assertCount(1, $filtered);
        $this->assertSame('Admin Event A', $filtered->first()->summary);
    }

    public function test_admin_inbox_does_not_query_tenant_db_before_tenant_selection(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertFalse(tenancy()->initialized);

        $page = Livewire::test(MessengerInboxPage::class)->instance();

        $this->assertNull($page->selectedTenantId);
        $this->assertFalse(tenancy()->initialized);
        $this->assertTrue($page->conversations->isEmpty());
    }

    public function test_admin_inbox_resets_context_on_tenant_clear_and_switch(): void
    {
        $tenantA = $this->createTenantWithDatabase();
        $tenantB = $this->createTenantWithDatabase();
        $admin = $this->createAdmin();

        $page = $this->makeInboxPage($admin);

        $page->selectedTenantId = $tenantA->id;
        $page->updatedSelectedTenantId();
        $this->assertTrue(tenancy()->initialized);
        $this->assertSame($tenantA->id, tenant('id'));

        $page->selectedTenantId = $tenantB->id;
        $page->updatedSelectedTenantId();
        $this->assertSame($tenantB->id, tenant('id'));

        $page->selectedTenantId = null;
        $page->updatedSelectedTenantId();
        $this->assertNull($page->selectedConversationId);
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_admin_inbox_shows_only_selected_tenant_conversations(): void
    {
        $tenantA = $this->createTenantWithDatabase();
        $tenantB = $this->createTenantWithDatabase();
        $admin = $this->createAdmin();

        $tenantA->run(function () {
            $page = MessengerPage::query()->create([
                'page_id' => 'page-a-inbox',
                'page_name' => 'A Page',
                'page_access_token' => 'token-a',
            ]);
            MessengerConversation::query()->create([
                'messenger_page_id' => $page->id,
                'sender_psid' => 'psid-a',
                'customer_name' => 'Customer A Only',
                'status' => 'open',
                'last_message_at' => now(),
                'customer_service_window_expires_at' => now()->addHour(),
            ]);
        });

        $tenantB->run(function () {
            $page = MessengerPage::query()->create([
                'page_id' => 'page-b-inbox',
                'page_name' => 'B Page',
                'page_access_token' => 'token-b',
            ]);
            MessengerConversation::query()->create([
                'messenger_page_id' => $page->id,
                'sender_psid' => 'psid-b',
                'customer_name' => 'Customer B Only',
                'status' => 'open',
                'last_message_at' => now(),
                'customer_service_window_expires_at' => now()->addHour(),
            ]);
        });

        $page = $this->makeInboxPage($admin);
        $page->selectedTenantId = $tenantA->id;
        $page->updatedSelectedTenantId();

        $names = $page->conversations->pluck('customer_name')->all();
        $this->assertSame(['Customer A Only'], $names);
        $this->assertNotContains('Customer B Only', $names);
    }

    public function test_admin_inbox_reply_uses_send_action_not_graph_directly(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'recipient_id' => 'psid-admin',
                'message_id' => 'mid.ADMIN.OUT1',
            ], 200),
        ]);

        $tenant = $this->createTenantWithDatabase();
        $admin = $this->createAdmin();

        $conversationId = null;

        $tenant->run(function () use (&$conversationId) {
            $page = MessengerPage::query()->create([
                'page_id' => 'page-admin-reply',
                'page_name' => 'Reply Page',
                'page_access_token' => 'token',
            ]);
            $conversationId = MessengerConversation::query()->create([
                'messenger_page_id' => $page->id,
                'sender_psid' => 'psid-admin',
                'status' => 'open',
                'customer_service_window_expires_at' => now()->addHours(4),
            ])->id;
        });

        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(MessengerInboxPage::class)
            ->set('selectedTenantId', $tenant->id)
            ->set('selectedConversationId', $conversationId)
            ->set('replyBody', 'Admin reply')
            ->call('sendAdminReply')
            ->assertHasNoErrors();

        $tenant->run(function () {
            $this->assertSame(1, MessengerMessage::query()->where('provider_message_id', 'mid.ADMIN.OUT1')->count());
            $this->assertSame('outbound', MessengerMessage::query()->first()->direction->value);
        });

        Http::assertSentCount(1);

        $traitUses = class_uses_recursive(MessengerInboxPage::class);
        $this->assertContains(InteractsWithMessengerInbox::class, $traitUses);

        $source = file_get_contents((new ReflectionClass(InteractsWithMessengerInbox::class))->getFileName());
        $this->assertStringContainsString('SendMessengerTextMessageAction', $source);
        $this->assertStringNotContainsString('MessengerGraphApiService', $source);
    }

    protected function makeInboxPage(Admin $admin): MessengerInboxPage
    {
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return Livewire::test(MessengerInboxPage::class)->instance();
    }

    protected function createAdmin(): Admin
    {
        $admin = Admin::query()->create([
            'name' => 'Messenger Admin',
            'email' => 'messenger-admin-'.str()->uuid().'@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $admin->givePermissionTo([
            'messenger.platform.view_all_pages',
            'messenger.platform.manage_all_pages',
            'messenger.platform.view_webhook_events',
            'messenger.platform.troubleshoot',
        ]);

        return $admin;
    }
}
