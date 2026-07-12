<?php

namespace Tests\Feature\Messenger;

use App\Filament\Tenant\Pages\MessengerInboxPage;
use App\Filament\Tenant\Resources\MessengerPages\Pages\EditMessengerPage;
use App\Filament\Tenant\Resources\MessengerWebhookEvents\MessengerWebhookEventResource;
use App\Messenger\Enums\MessengerWebhookProcessingStatus;
use App\Models\MessengerPageRegistry;
use App\Models\MessengerWebhookEvent;
use App\Models\Tenant\MessengerConversation;
use App\Models\Tenant\MessengerMessage;
use App\Models\Tenant\MessengerPage;
use App\Models\TenantUser;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

class MessengerTenantUiTest extends MessengerTestCase
{
    public function test_page_token_stays_encrypted_hidden_and_empty_edit_keeps_old_token(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'agent@messenger.test',
                'password' => 'password',
            ]);
            $this->actingAs($user, 'tenant');
            Filament::setCurrentPanel(Filament::getPanel('tenant'));

            $page = MessengerPage::query()->create([
                'page_id' => 'page-token-ui',
                'page_name' => 'Token Page',
                'page_access_token' => 'secret-original-token',
            ]);

            $this->assertSame('********', $page->masked_page_access_token);
            $this->assertArrayNotHasKey('page_access_token', $page->toArray());
            $this->assertNotSame('secret-original-token', $page->getAttributes()['page_access_token'] ?? null);

            Livewire::test(EditMessengerPage::class, ['record' => $page->getKey()])
                ->fillForm([
                    'page_name' => 'Token Page Updated',
                    'page_access_token' => '',
                ])
                ->call('save')
                ->assertHasNoFormErrors();

            $page->refresh();
            $this->assertSame('Token Page Updated', $page->page_name);
            $this->assertSame('secret-original-token', $page->page_access_token);

            $registry = MessengerPageRegistry::query()->where('page_id', 'page-token-ui')->first();
            $this->assertNotNull($registry);
            $this->assertArrayNotHasKey('page_access_token', $registry->getAttributes());
        });
    }

    public function test_tenant_pages_are_isolated_per_tenant_database(): void
    {
        $tenantA = $this->createTenantWithDatabase();
        $tenantB = $this->createTenantWithDatabase();

        $tenantA->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-only-a',
                'page_name' => 'A',
                'page_access_token' => 'token-a',
            ]);
        });

        $tenantB->run(function () {
            $this->assertNull(MessengerPage::query()->where('page_id', 'page-only-a')->first());
            $this->assertSame(0, MessengerPage::query()->count());
        });

        $tenantA->run(function () {
            $this->assertSame(1, MessengerPage::query()->where('page_id', 'page-only-a')->count());
        });
    }

    public function test_inbox_page_renders_conversations_for_current_tenant(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'inbox@messenger.test',
                'password' => 'password',
            ]);
            $this->actingAs($user, 'tenant');
            Filament::setCurrentPanel(Filament::getPanel('tenant'));

            $page = MessengerPage::query()->create([
                'page_id' => 'page-inbox',
                'page_name' => 'Inbox Page',
                'page_access_token' => 'token',
            ]);

            $conversation = MessengerConversation::query()->create([
                'messenger_page_id' => $page->id,
                'sender_psid' => 'psid-inbox',
                'customer_name' => 'Customer One',
                'status' => 'open',
                'last_message_preview' => 'Hi',
                'last_message_at' => now(),
                'customer_service_window_expires_at' => now()->addHour(),
            ]);

            Livewire::test(MessengerInboxPage::class)
                ->assertSuccessful()
                ->assertSee('Customer One')
                ->assertSee('Inbox Page')
                ->assertSet('selectedConversationId', $conversation->id);
        });
    }

    public function test_inbox_reply_inside_24h_persists_outbound_message(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'recipient_id' => 'psid-reply',
                'message_id' => 'mid.UI.OUT1',
            ], 200),
        ]);

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'reply@messenger.test',
                'password' => 'password',
            ]);
            $this->actingAs($user, 'tenant');
            Filament::setCurrentPanel(Filament::getPanel('tenant'));

            $page = MessengerPage::query()->create([
                'page_id' => 'page-reply',
                'page_name' => 'Reply Page',
                'page_access_token' => 'token',
            ]);

            $conversation = MessengerConversation::query()->create([
                'messenger_page_id' => $page->id,
                'sender_psid' => 'psid-reply',
                'status' => 'open',
                'customer_service_window_expires_at' => now()->addHours(6),
            ]);

            Livewire::test(MessengerInboxPage::class)
                ->set('selectedConversationId', $conversation->id)
                ->set('replyBody', 'Hello from inbox')
                ->call('sendReplyAction')
                ->assertHasNoErrors();

            $this->assertSame(1, MessengerMessage::query()->where('provider_message_id', 'mid.UI.OUT1')->count());
            $this->assertSame('outbound', MessengerMessage::query()->first()->direction->value);
            Http::assertSentCount(1);
        });
    }

    public function test_inbox_reply_outside_24h_is_blocked_before_graph(): void
    {
        Http::fake();

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'blocked@messenger.test',
                'password' => 'password',
            ]);
            $this->actingAs($user, 'tenant');
            Filament::setCurrentPanel(Filament::getPanel('tenant'));

            $page = MessengerPage::query()->create([
                'page_id' => 'page-blocked',
                'page_name' => 'Blocked Page',
                'page_access_token' => 'token',
            ]);

            $conversation = MessengerConversation::query()->create([
                'messenger_page_id' => $page->id,
                'sender_psid' => 'psid-blocked',
                'status' => 'open',
                'customer_service_window_expires_at' => now()->subMinute(),
            ]);

            Livewire::test(MessengerInboxPage::class)
                ->set('selectedConversationId', $conversation->id)
                ->set('replyBody', 'Should not send')
                ->call('sendReplyAction');

            $this->assertSame(0, MessengerMessage::query()->count());
            Http::assertNothingSent();
        });
    }

    public function test_webhook_event_resource_only_shows_current_tenant_events(): void
    {
        $tenantA = $this->createTenantWithDatabase();
        $tenantB = $this->createTenantWithDatabase();

        MessengerWebhookEvent::query()->create([
            'provider' => 'meta',
            'event_type' => 'page',
            'summary' => 'Event A',
            'page_id' => 'page-a',
            'tenant_id' => $tenantA->id,
            'processing_status' => MessengerWebhookProcessingStatus::Processed,
            'payload' => ['object' => 'page'],
            'original_payload' => ['object' => 'page'],
        ]);

        MessengerWebhookEvent::query()->create([
            'provider' => 'meta',
            'event_type' => 'page',
            'summary' => 'Event B',
            'page_id' => 'page-b',
            'tenant_id' => $tenantB->id,
            'processing_status' => MessengerWebhookProcessingStatus::Processed,
            'payload' => ['object' => 'page'],
            'original_payload' => ['object' => 'page'],
        ]);

        $tenantA->run(function () use ($tenantA) {
            $ids = MessengerWebhookEventResource::getEloquentQuery()->pluck('tenant_id')->unique()->all();

            $this->assertSame([$tenantA->id], $ids);
            $this->assertSame(1, MessengerWebhookEventResource::getEloquentQuery()->count());
            $this->assertSame('Event A', MessengerWebhookEventResource::getEloquentQuery()->first()->summary);
        });
    }
}
