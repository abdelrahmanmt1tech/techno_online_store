<?php

namespace Tests\Feature\Messenger;

class MessengerWebhookVerificationTest extends MessengerTestCase
{
    public function test_webhook_verification_succeeds_with_correct_token(): void
    {
        $response = $this->get('/webhooks/meta/messenger?hub.mode=subscribe&hub.verify_token=messenger-test-verify-token&hub.challenge=12345');

        $response->assertOk();
        $response->assertSee('12345');
    }

    public function test_webhook_verification_fails_with_wrong_token(): void
    {
        $response = $this->get('/webhooks/meta/messenger?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=12345');

        $response->assertForbidden();
    }

    public function test_webhook_verification_supports_underscore_query_params_as_fallback(): void
    {
        $response = $this->get('/webhooks/meta/messenger?hub_mode=subscribe&hub_verify_token=messenger-test-verify-token&hub_challenge=54321');

        $response->assertOk();
        $response->assertSee('54321');
    }

    public function test_webhook_verification_trims_configured_and_received_tokens(): void
    {
        config(['messenger.webhook_verify_token' => ' messenger-test-verify-token ']);

        $response = $this->get('/webhooks/meta/messenger?hub.mode=subscribe&hub.verify_token=messenger-test-verify-token&hub.challenge=99999');

        $response->assertOk();
        $response->assertSee('99999');
    }

    public function test_webhook_verification_fails_when_configured_token_is_empty(): void
    {
        config(['messenger.webhook_verify_token' => '']);

        $response = $this->get('/webhooks/meta/messenger?hub.mode=subscribe&hub.verify_token=anything&hub.challenge=12345');

        $response->assertForbidden();
    }

    public function test_webhook_verification_fails_when_mode_is_not_subscribe(): void
    {
        $response = $this->get('/webhooks/meta/messenger?hub.mode=unsubscribe&hub.verify_token=messenger-test-verify-token&hub.challenge=12345');

        $response->assertForbidden();
    }
}
