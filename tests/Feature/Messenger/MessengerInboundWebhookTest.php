<?php

namespace Tests\Feature\Messenger;

use App\Messenger\Actions\ProcessInboundMessengerMessageAction;
use App\Messenger\Enums\MessengerWebhookProcessingStatus;
use App\Messenger\Jobs\ProcessMessengerWebhookJob;
use App\Messenger\Services\MessengerWebhookPayloadRedactor;
use App\Messenger\Services\MessengerWebhookResolver;
use App\Models\MessengerWebhookEvent;
use App\Models\Tenant\MessengerContact;
use App\Models\Tenant\MessengerConversation;
use App\Models\Tenant\MessengerMessage;
use App\Models\Tenant\MessengerPage;

class MessengerInboundWebhookTest extends MessengerTestCase
{
    public function test_post_invalid_signature_is_rejected(): void
    {
        config([
            'messenger.app_secret' => 'test-app-secret',
            'messenger.allow_unsigned_webhooks' => false,
        ]);

        $payload = $this->inboundTextPayload();

        $response = $this->call(
            'POST',
            '/webhooks/meta/messenger',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
            ],
            json_encode($payload),
        );

        $response->assertForbidden();
        $this->assertNotSame(419, $response->status(), 'Messenger webhook must not fail CSRF (419)');

        $event = MessengerWebhookEvent::query()->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertSame(MessengerWebhookProcessingStatus::Rejected, $event->processing_status);
        $this->assertFalse($event->signature_valid);
        $this->assertSame('invalid_signature', $event->event_type);
    }

    public function test_valid_inbound_message_creates_contact_conversation_message_and_window(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-123',
                'page_name' => 'Store Page',
                'page_access_token' => 'page-token',
            ]);
        });

        $payload = $this->inboundTextPayload();

        $this->postJson('/webhooks/meta/messenger', $payload)->assertOk();

        $event = MessengerWebhookEvent::query()->latest('id')->first();
        $this->assertNotNull($event);

        (new ProcessMessengerWebhookJob($event->id))->handle(
            app(MessengerWebhookResolver::class),
            app(ProcessInboundMessengerMessageAction::class),
            app(MessengerWebhookPayloadRedactor::class),
        );

        $event->refresh();
        $this->assertSame(MessengerWebhookProcessingStatus::Processed, $event->processing_status);
        $this->assertSame($tenant->id, $event->tenant_id);
        $this->assertSame('page-123', $event->page_id);

        $tenant->run(function () {
            $this->assertSame(1, MessengerContact::query()->where('psid', 'psid-456')->count());
            $this->assertSame(1, MessengerConversation::query()->count());
            $this->assertSame(1, MessengerMessage::query()->where('provider_message_id', 'mid.TEST123')->count());

            $conversation = MessengerConversation::query()->first();
            $this->assertNotNull($conversation->customer_service_window_expires_at);
            $this->assertTrue($conversation->canSendFreeformReply());

            $message = MessengerMessage::query()->first();
            $this->assertSame('Hello', $message->body);
            $this->assertSame('inbound', $message->direction->value);
        });
    }

    public function test_unresolved_page_id_marks_event_unresolved(): void
    {
        $payload = $this->inboundTextPayload(pageId: 'unknown-page');

        $this->postJson('/webhooks/meta/messenger', $payload)->assertOk();

        $event = MessengerWebhookEvent::query()->latest('id')->first();
        (new ProcessMessengerWebhookJob($event->id))->handle(
            app(MessengerWebhookResolver::class),
            app(ProcessInboundMessengerMessageAction::class),
            app(MessengerWebhookPayloadRedactor::class),
        );

        $event->refresh();
        $this->assertSame(MessengerWebhookProcessingStatus::Unresolved, $event->processing_status);
        $this->assertSame('unknown-page', $event->page_id);
        $this->assertStringContainsString('page_id', (string) $event->error_message);
    }

    public function test_duplicate_provider_message_id_does_not_create_duplicate_message(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-123',
                'page_name' => 'Store Page',
                'page_access_token' => 'page-token',
            ]);
        });

        $payload = $this->inboundTextPayload(mid: 'mid.DUPLICATE');
        $jobDeps = [
            app(MessengerWebhookResolver::class),
            app(ProcessInboundMessengerMessageAction::class),
            app(MessengerWebhookPayloadRedactor::class),
        ];

        $this->postJson('/webhooks/meta/messenger', $payload)->assertOk();
        (new ProcessMessengerWebhookJob(MessengerWebhookEvent::query()->latest('id')->first()->id))->handle(...$jobDeps);

        $this->postJson('/webhooks/meta/messenger', $payload)->assertOk();
        (new ProcessMessengerWebhookJob(MessengerWebhookEvent::query()->latest('id')->first()->id))->handle(...$jobDeps);

        $tenant->run(function () {
            $this->assertSame(1, MessengerMessage::query()->where('provider_message_id', 'mid.DUPLICATE')->count());
        });
    }

    public function test_inbound_message_stays_in_owning_tenant_only(): void
    {
        $tenantA = $this->createTenantWithDatabase();
        $tenantB = $this->createTenantWithDatabase();

        $tenantA->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-tenant-a',
                'page_name' => 'A Page',
                'page_access_token' => 'token-a',
            ]);
        });

        $payload = $this->inboundTextPayload(pageId: 'page-tenant-a', psid: 'psid-a', mid: 'mid.A1');

        $this->postJson('/webhooks/meta/messenger', $payload)->assertOk();
        (new ProcessMessengerWebhookJob(MessengerWebhookEvent::query()->latest('id')->first()->id))->handle(
            app(MessengerWebhookResolver::class),
            app(ProcessInboundMessengerMessageAction::class),
            app(MessengerWebhookPayloadRedactor::class),
        );

        $tenantA->run(function () {
            $this->assertSame(1, MessengerMessage::query()->count());
            $this->assertSame(1, MessengerContact::query()->where('psid', 'psid-a')->count());
        });

        $tenantB->run(function () {
            $this->assertSame(0, MessengerMessage::query()->count());
            $this->assertSame(0, MessengerContact::query()->count());
            $this->assertNull(MessengerPage::query()->where('page_id', 'page-tenant-a')->first());
        });
    }
}
