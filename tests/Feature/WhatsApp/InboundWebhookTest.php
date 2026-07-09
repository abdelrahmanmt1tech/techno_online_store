<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Tenant\WhatsAppConversation;
use App\Models\Tenant\WhatsAppMessage;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\WhatsAppWebhookEvent;
use App\WhatsApp\Actions\ProcessInboundMessageAction;
use App\WhatsApp\Actions\ProcessMessageStatusAction;
use App\WhatsApp\Jobs\ProcessWhatsAppWebhookJob;
use App\WhatsApp\Services\WhatsAppWebhookPayloadRedactor;
use App\WhatsApp\Services\WhatsAppWebhookResolver;

class InboundWebhookTest extends WhatsAppTestCase
{
    public function test_inbound_webhook_creates_conversation_and_message(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            WhatsAppNumber::query()->create([
                'display_phone_number' => '+201234567890',
                'phone_number_id' => '123456789',
                'whatsapp_business_account_id' => 'waba-1',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_active' => true,
                'is_default' => false,
            ]);
        });

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'metadata' => ['phone_number_id' => '123456789'],
                        'contacts' => [['profile' => ['name' => 'Customer']]],
                        'messages' => [[
                            'id' => 'wamid.TEST123',
                            'from' => '201111111111',
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => 'Hello'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/webhooks/meta/whatsapp', $payload);
        $response->assertOk();

        $eventId = WhatsAppWebhookEvent::query()->first()->id;
        (new ProcessWhatsAppWebhookJob($eventId))->handle(
            app(WhatsAppWebhookResolver::class),
            app(ProcessInboundMessageAction::class),
            app(ProcessMessageStatusAction::class),
            app(WhatsAppWebhookPayloadRedactor::class),
        );

        $tenant->run(function () {
            $this->assertSame(1, WhatsAppConversation::query()->count());
            $this->assertSame(1, WhatsAppMessage::query()->count());
            $conversation = WhatsAppConversation::query()->first();
            $this->assertNotNull($conversation->customer_service_window_expires_at);
            $this->assertTrue($conversation->canSendFreeformReply());
        });
    }

    public function test_duplicate_inbound_webhook_does_not_duplicate_message(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            WhatsAppNumber::query()->create([
                'display_phone_number' => '+201234567890',
                'phone_number_id' => '123456789',
                'whatsapp_business_account_id' => 'waba-1',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_active' => true,
                'is_default' => false,
            ]);
        });

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => '123456789'],
                        'messages' => [[
                            'id' => 'wamid.DUPLICATE',
                            'from' => '201111111111',
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => 'Hello'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $jobDeps = [
            app(WhatsAppWebhookResolver::class),
            app(ProcessInboundMessageAction::class),
            app(ProcessMessageStatusAction::class),
            app(WhatsAppWebhookPayloadRedactor::class),
        ];

        $this->postJson('/webhooks/meta/whatsapp', $payload)->assertOk();
        (new ProcessWhatsAppWebhookJob(WhatsAppWebhookEvent::query()->latest('id')->first()->id))->handle(...$jobDeps);

        $this->postJson('/webhooks/meta/whatsapp', $payload)->assertOk();
        (new ProcessWhatsAppWebhookJob(WhatsAppWebhookEvent::query()->latest('id')->first()->id))->handle(...$jobDeps);

        $tenant->run(function () {
            $this->assertSame(1, WhatsAppMessage::query()->where('provider_message_id', 'wamid.DUPLICATE')->count());
        });
    }
}
