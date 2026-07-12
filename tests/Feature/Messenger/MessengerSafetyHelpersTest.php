<?php

namespace Tests\Feature\Messenger;

use App\Filament\Shared\Messenger\Concerns\ChecksMessengerPermissions;
use App\Filament\Tenant\Resources\MessengerPages\MessengerPageResource;
use App\Messenger\Services\MessengerWebhookPayloadRedactor;
use App\Messenger\Services\MessengerWebhookRequestLogger;
use ReflectionMethod;

class MessengerSafetyHelpersTest extends MessengerTestCase
{
    public function test_payload_redactor_masks_psid_and_message_text(): void
    {
        config(['messenger.webhook_payload_retention' => 'minimized']);

        $redacted = app(MessengerWebhookPayloadRedactor::class)->redact([
            'object' => 'page',
            'entry' => [[
                'id' => 'page-123',
                'messaging' => [[
                    'sender' => ['id' => 'psid-secret'],
                    'recipient' => ['id' => 'page-123'],
                    'timestamp' => 1,
                    'message' => [
                        'mid' => 'mid.1',
                        'text' => 'Secret customer text',
                    ],
                ]],
            ]],
        ]);

        $this->assertSame('[redacted]', $redacted['entry'][0]['messaging'][0]['sender']['id']);
        $this->assertSame('[redacted]', $redacted['entry'][0]['messaging'][0]['message']['text']);
        $this->assertSame('mid.1', $redacted['entry'][0]['messaging'][0]['message']['mid']);
        $this->assertStringNotContainsString('Secret customer text', json_encode($redacted));
        $this->assertStringNotContainsString('page_access_token', json_encode($redacted));
    }

    public function test_request_logger_masks_secrets(): void
    {
        $logger = app(MessengerWebhookRequestLogger::class);
        $method = new ReflectionMethod($logger, 'maskSecret');
        $method->setAccessible(true);

        $masked = $method->invoke($logger, 'super-secret-verify-token');

        $this->assertStringContainsString('*', $masked);
        $this->assertStringNotContainsString('secret-verify', $masked);
    }

    public function test_tenant_permission_checks_respect_bypass_flag(): void
    {
        config(['app.bypass_permissions' => true]);
        $this->assertTrue(MessengerPageResource::canViewAny());

        config(['app.bypass_permissions' => false]);
        $this->assertFalse(MessengerPageResource::canViewAny());
    }

    public function test_checks_messenger_permissions_trait_exists_for_resources(): void
    {
        $this->assertContains(
            ChecksMessengerPermissions::class,
            class_uses_recursive(MessengerPageResource::class),
        );
    }
}
