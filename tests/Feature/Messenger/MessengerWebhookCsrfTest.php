<?php

namespace Tests\Feature\Messenger;

use App\Messenger\Enums\MessengerWebhookProcessingStatus;
use App\Models\MessengerWebhookEvent;
use App\Models\Tenant\MessengerPage;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use ReflectionMethod;

class MessengerWebhookCsrfTest extends MessengerTestCase
{
    public function test_messenger_webhook_path_is_excluded_from_csrf_middleware(): void
    {
        $middleware = $this->app->make(ValidateCsrfToken::class);
        $method = new ReflectionMethod($middleware, 'inExceptArray');
        $method->setAccessible(true);

        $request = Request::create('/webhooks/meta/messenger', 'POST');

        $this->assertTrue(
            $method->invoke($middleware, $request),
            'POST /webhooks/meta/messenger must be in CSRF except list (Meta cannot send _token).',
        );
    }

    public function test_whatsapp_webhook_csrf_exception_is_preserved(): void
    {
        $middleware = $this->app->make(ValidateCsrfToken::class);
        $method = new ReflectionMethod($middleware, 'inExceptArray');
        $method->setAccessible(true);

        $request = Request::create('/webhooks/meta/whatsapp', 'POST');

        $this->assertTrue($method->invoke($middleware, $request));
    }

    public function test_invalid_signature_returns_forbidden_not_csrf_419(): void
    {
        config([
            'messenger.app_secret' => 'test-app-secret',
            'messenger.allow_unsigned_webhooks' => false,
        ]);

        $payload = json_encode($this->inboundTextPayload());

        $response = $this->call(
            'POST',
            '/webhooks/meta/messenger',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => 'sha256=deadbeef',
            ],
            $payload,
        );

        $this->assertNotSame(419, $response->status(), 'CSRF must not reject Messenger webhook POST');
        $response->assertForbidden();
        $response->assertSee('Forbidden');

        $event = MessengerWebhookEvent::query()->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertSame(MessengerWebhookProcessingStatus::Rejected, $event->processing_status);
        $this->assertSame('invalid_signature', $event->event_type);
    }

    public function test_valid_signed_webhook_returns_ok_not_csrf_419(): void
    {
        $secret = 'test-app-secret';
        config([
            'messenger.app_secret' => $secret,
            'messenger.allow_unsigned_webhooks' => false,
        ]);

        $tenant = $this->createTenantWithDatabase();
        $tenant->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-123',
                'page_name' => 'CSRF Page',
                'page_access_token' => 'token',
            ]);
        });

        $payloadArray = $this->inboundTextPayload(mid: 'mid.CSRF.OK');
        $rawBody = json_encode($payloadArray);
        $signature = 'sha256='.hash_hmac('sha256', $rawBody, $secret);

        $response = $this->call(
            'POST',
            '/webhooks/meta/messenger',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $rawBody,
        );

        $this->assertNotSame(419, $response->status(), 'CSRF must not reject Messenger webhook POST');
        $response->assertOk();
        $response->assertSee('OK');

        $event = MessengerWebhookEvent::query()->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->signature_valid);
        $this->assertNotSame(MessengerWebhookProcessingStatus::Rejected, $event->processing_status);
    }
}
