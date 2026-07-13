<?php

namespace Tests\Feature\Messenger;

use App\Messenger\Actions\SendMessengerTextMessageAction;
use App\Messenger\Enums\MessengerApiRequestOperation;
use App\Messenger\Enums\MessengerApiRequestOutcome;
use App\Messenger\Enums\MessengerPageStatus;
use App\Models\Tenant\MessengerApiRequest;
use App\Models\Tenant\MessengerConversation;
use App\Models\Tenant\MessengerPage;
use Illuminate\Support\Facades\Http;

class MessengerApiRequestLogTest extends MessengerTestCase
{
    public function test_outbound_text_send_creates_api_request_log_with_payload_and_response(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'recipient_id' => 'psid-456',
                'message_id' => 'mid.LOG1',
            ], 200),
        ]);

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $page = MessengerPage::query()->create([
                'page_id' => 'page-123',
                'page_name' => 'Store Page',
                'page_access_token' => 'secret-page-token',
                'status' => MessengerPageStatus::Active,
                'is_active' => true,
            ]);

            $conversation = MessengerConversation::query()->create([
                'messenger_page_id' => $page->id,
                'sender_psid' => 'psid-456',
                'status' => 'open',
                'customer_service_window_expires_at' => now()->addHours(12),
                'last_customer_message_at' => now()->subHour(),
            ]);

            $message = app(SendMessengerTextMessageAction::class)->execute(
                $conversation,
                'Hello from CRM',
            );

            $log = MessengerApiRequest::query()->first();

            $this->assertNotNull($log);
            $this->assertSame(MessengerApiRequestOperation::SendText, $log->operation);
            $this->assertSame(MessengerApiRequestOutcome::Success, $log->outcome);
            $this->assertSame('psid-456', $log->recipient_psid);
            $this->assertSame($page->id, $log->messenger_page_id);
            $this->assertSame($message->id, $log->messenger_message_id);
            $this->assertSame(200, $log->http_status);
            $this->assertNotSame('', $log->summary);
            $this->assertNotSame('', $log->status_label);
            $this->assertSame('RESPONSE', data_get($log->request_payload, 'messaging_type'));
            $this->assertSame('Hello from CRM', data_get($log->request_payload, 'message.text'));
            $this->assertSame('mid.LOG1', data_get($log->response_body, 'message_id'));
            $this->assertStringNotContainsString('secret-page-token', json_encode($log->request_payload));
            $this->assertStringNotContainsString('secret-page-token', json_encode($log->response_body));
        });
    }

    public function test_failed_outbound_send_still_logs_request_with_failed_outcome(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token.',
                    'code' => 190,
                ],
            ], 401),
        ]);

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $page = MessengerPage::query()->create([
                'page_id' => 'page-fail',
                'page_name' => 'Fail Page',
                'page_access_token' => 'secret-page-token',
                'status' => MessengerPageStatus::Active,
                'is_active' => true,
            ]);

            $conversation = MessengerConversation::query()->create([
                'messenger_page_id' => $page->id,
                'sender_psid' => 'psid-789',
                'status' => 'open',
                'customer_service_window_expires_at' => now()->addHour(),
                'last_customer_message_at' => now()->subMinutes(10),
            ]);

            try {
                app(SendMessengerTextMessageAction::class)->execute($conversation, 'Will fail');
                $this->fail('Expected RuntimeException');
            } catch (\RuntimeException) {
                // expected
            }

            $log = MessengerApiRequest::query()->first();

            $this->assertNotNull($log);
            $this->assertSame(MessengerApiRequestOutcome::Failed, $log->outcome);
            $this->assertSame('190', $log->api_error_code);
            $this->assertSame(401, $log->http_status);
            $this->assertNotNull($log->messenger_message_id);
            $this->assertStringContainsString('Authentication', $log->status_label);
        });
    }
}
