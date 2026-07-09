<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Tenant\WhatsAppConversation;
use App\Models\Tenant\WhatsAppMessage;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\WhatsAppWebhookEvent;
use App\WhatsApp\Actions\ProcessInboundMessageAction;
use App\WhatsApp\Actions\ProcessMessageStatusAction;
use App\WhatsApp\Enums\WhatsAppMessageStatus;
use App\WhatsApp\Jobs\ProcessWhatsAppWebhookJob;
use App\WhatsApp\Services\WhatsAppWebhookPayloadRedactor;
use App\WhatsApp\Services\WhatsAppWebhookResolver;

class StatusWebhookTest extends WhatsAppTestCase
{
    public function test_status_webhook_updates_existing_outbound_message(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201234567890',
                'phone_number_id' => '123456789',
                'whatsapp_business_account_id' => 'waba-1',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_active' => true,
                'is_default' => false,
            ]);

            $conversation = WhatsAppConversation::query()->create([
                'whatsapp_number_id' => $number->id,
                'customer_phone' => '201111111111',
                'status' => 'open',
            ]);

            WhatsAppMessage::query()->create([
                'conversation_id' => $conversation->id,
                'whatsapp_number_id' => $number->id,
                'provider_message_id' => 'wamid.OUT1',
                'direction' => 'outbound',
                'sender_type' => 'agent',
                'type' => 'text',
                'body' => 'Hi',
                'status' => WhatsAppMessageStatus::Sent,
                'sent_at' => now(),
            ]);

        });

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => '123456789'],
                        'statuses' => [[
                            'id' => 'wamid.OUT1',
                            'status' => 'delivered',
                            'timestamp' => (string) now()->timestamp,
                            'recipient_id' => '201111111111',
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/webhooks/meta/whatsapp', $payload)->assertOk();
        (new ProcessWhatsAppWebhookJob(WhatsAppWebhookEvent::query()->latest('id')->first()->id))->handle(
            app(WhatsAppWebhookResolver::class),
            app(ProcessInboundMessageAction::class),
            app(ProcessMessageStatusAction::class),
            app(WhatsAppWebhookPayloadRedactor::class),
        );

        $tenant->run(function () {
            $message = WhatsAppMessage::query()->where('provider_message_id', 'wamid.OUT1')->first();
            $this->assertSame(WhatsAppMessageStatus::Delivered, $message->status);
        });
    }

    public function test_read_status_does_not_downgrade_to_delivered(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201234567890',
                'phone_number_id' => '123456789',
                'whatsapp_business_account_id' => 'waba-1',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_active' => true,
                'is_default' => false,
            ]);

            $conversation = WhatsAppConversation::query()->create([
                'whatsapp_number_id' => $number->id,
                'customer_phone' => '201111111111',
                'status' => 'open',
            ]);

            WhatsAppMessage::query()->create([
                'conversation_id' => $conversation->id,
                'whatsapp_number_id' => $number->id,
                'provider_message_id' => 'wamid.READ1',
                'direction' => 'outbound',
                'sender_type' => 'agent',
                'type' => 'text',
                'body' => 'Hi',
                'status' => WhatsAppMessageStatus::Read,
                'read_at' => now(),
            ]);
        });

        $statusPayload = [
            'id' => 'wamid.READ1',
            'status' => 'delivered',
            'timestamp' => (string) now()->timestamp,
            'recipient_id' => '201111111111',
        ];

        $tenant->run(function () use ($statusPayload) {
            app(ProcessMessageStatusAction::class)->execute($statusPayload);
        });

        $tenant->run(function () {
            $message = WhatsAppMessage::query()->where('provider_message_id', 'wamid.READ1')->first();
            $this->assertSame(WhatsAppMessageStatus::Read, $message->status);
        });
    }

    public function test_read_status_does_not_downgrade_to_sent(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201234567890',
                'phone_number_id' => '123456789',
                'whatsapp_business_account_id' => 'waba-1',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_active' => true,
                'is_default' => false,
            ]);

            $conversation = WhatsAppConversation::query()->create([
                'whatsapp_number_id' => $number->id,
                'customer_phone' => '201111111111',
                'status' => 'open',
            ]);

            WhatsAppMessage::query()->create([
                'conversation_id' => $conversation->id,
                'whatsapp_number_id' => $number->id,
                'provider_message_id' => 'wamid.READ2',
                'direction' => 'outbound',
                'sender_type' => 'agent',
                'type' => 'text',
                'body' => 'Hi',
                'status' => WhatsAppMessageStatus::Read,
                'read_at' => now(),
            ]);
        });

        $tenant->run(function () {
            app(ProcessMessageStatusAction::class)->execute([
                'id' => 'wamid.READ2',
                'status' => 'sent',
                'timestamp' => (string) now()->timestamp,
            ]);
        });

        $tenant->run(function () {
            $message = WhatsAppMessage::query()->where('provider_message_id', 'wamid.READ2')->first();
            $this->assertSame(WhatsAppMessageStatus::Read, $message->status);
        });
    }
}
