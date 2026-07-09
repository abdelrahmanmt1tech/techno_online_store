<?php

namespace Tests\Feature\WhatsApp;

class WebhookVerificationTest extends WhatsAppTestCase
{
    public function test_webhook_verification_succeeds_with_correct_token(): void
    {
        $response = $this->get('/webhooks/meta/whatsapp?hub.mode=subscribe&hub.verify_token=test-verify-token&hub.challenge=12345');

        $response->assertOk();
        $response->assertSee('12345');
    }

    public function test_webhook_verification_fails_with_wrong_token(): void
    {
        $response = $this->get('/webhooks/meta/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=12345');

        $response->assertForbidden();
    }

    public function test_webhook_verification_supports_underscore_query_params_as_fallback(): void
    {
        $response = $this->get('/webhooks/meta/whatsapp?hub_mode=subscribe&hub_verify_token=test-verify-token&hub_challenge=54321');

        $response->assertOk();
        $response->assertSee('54321');
    }
}
