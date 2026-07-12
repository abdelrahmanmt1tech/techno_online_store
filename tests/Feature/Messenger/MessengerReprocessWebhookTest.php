<?php

namespace Tests\Feature\Messenger;

use App\Messenger\Actions\ReprocessMessengerWebhookAction;
use App\Messenger\Enums\MessengerWebhookProcessingStatus;
use App\Models\MessengerWebhookEvent;
use App\Models\Tenant\MessengerContact;
use App\Models\Tenant\MessengerMessage;
use App\Models\Tenant\MessengerPage;
use RuntimeException;

class MessengerReprocessWebhookTest extends MessengerTestCase
{
    public function test_reprocess_failed_event_uses_original_payload_and_resolves_by_page_id(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-reprocess',
                'page_name' => 'Reprocess Page',
                'page_access_token' => 'token',
            ]);
        });

        $payload = $this->inboundTextPayload(
            pageId: 'page-reprocess',
            psid: 'psid-reprocess',
            mid: 'mid.REPROCESS1',
            text: 'Reprocess me',
        );

        $event = MessengerWebhookEvent::query()->create([
            'provider' => 'meta',
            'event_type' => 'page',
            'summary' => 'Failed earlier',
            'page_id' => 'page-reprocess',
            'tenant_id' => null,
            'processing_status' => MessengerWebhookProcessingStatus::Failed,
            'payload' => ['object' => 'page', 'redacted' => true],
            'original_payload' => $payload,
            'error_message' => 'Temporary failure',
            'processed_at' => now()->subMinute(),
        ]);

        $this->assertTrue($event->canReprocess());

        app(ReprocessMessengerWebhookAction::class)->execute($event);

        // QUEUE_CONNECTION=sync processes the job during dispatch.
        $event->refresh();
        $this->assertSame(MessengerWebhookProcessingStatus::Processed, $event->processing_status);
        $this->assertSame($tenant->id, $event->tenant_id);

        $tenant->run(function () {
            $this->assertSame(1, MessengerContact::query()->where('psid', 'psid-reprocess')->count());
            $this->assertSame(1, MessengerMessage::query()->where('provider_message_id', 'mid.REPROCESS1')->count());
        });
    }

    public function test_invalid_signature_event_cannot_be_reprocessed(): void
    {
        $event = MessengerWebhookEvent::query()->create([
            'provider' => 'meta',
            'event_type' => 'invalid_signature',
            'summary' => 'Rejected',
            'processing_status' => MessengerWebhookProcessingStatus::Rejected,
            'payload' => null,
            'original_payload' => ['entry' => [['id' => 'x']]],
        ]);

        $this->assertFalse($event->canReprocess());

        $this->expectException(RuntimeException::class);
        app(ReprocessMessengerWebhookAction::class)->execute($event);
    }

    public function test_reprocess_does_not_trust_tenant_id_from_payload(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-trust',
                'page_name' => 'Trust Page',
                'page_access_token' => 'token',
            ]);
        });

        $payload = $this->inboundTextPayload(
            pageId: 'page-trust',
            psid: 'psid-trust',
            mid: 'mid.TRUST1',
        );
        $payload['tenant_id'] = 'forged-tenant-id-must-be-ignored';

        $event = MessengerWebhookEvent::query()->create([
            'provider' => 'meta',
            'event_type' => 'page',
            'summary' => 'Unresolved then reprocess',
            'page_id' => 'page-trust',
            'processing_status' => MessengerWebhookProcessingStatus::Unresolved,
            'payload' => $payload,
            'original_payload' => $payload,
            'error_message' => 'No registry entry for page_id.',
        ]);

        app(ReprocessMessengerWebhookAction::class)->execute($event);

        $event->refresh();
        $this->assertSame(MessengerWebhookProcessingStatus::Processed, $event->processing_status);
        $this->assertSame($tenant->id, $event->tenant_id);
        $this->assertNotSame('forged-tenant-id-must-be-ignored', $event->tenant_id);
    }
}
