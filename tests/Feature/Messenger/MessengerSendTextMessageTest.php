<?php

namespace Tests\Feature\Messenger;

use App\Messenger\Actions\SendMessengerTextMessageAction;
use App\Messenger\Enums\MessengerMessageDirection;
use App\Messenger\Enums\MessengerMessageStatus;
use App\Messenger\Enums\MessengerPageStatus;
use App\Messenger\Services\MessengerGraphApiService;
use App\Messenger\Services\MessengerSendingPolicyService;
use App\Models\MessengerPageRegistry;
use App\Models\Tenant\MessengerConversation;
use App\Models\Tenant\MessengerMessage;
use App\Models\Tenant\MessengerPage;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MessengerSendTextMessageTest extends MessengerTestCase
{
    public function test_send_inside_24h_window_succeeds_and_persists_outbound_message(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'recipient_id' => 'psid-456',
                'message_id' => 'mid.OUTBOUND1',
            ], 200),
        ]);

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            [$page, $conversation] = $this->seedPageAndConversation(
                windowExpiresAt: now()->addHours(12),
            );

            $message = app(SendMessengerTextMessageAction::class)->execute(
                $conversation,
                'Reply from CRM',
            );

            $this->assertSame('mid.OUTBOUND1', $message->provider_message_id);
            $this->assertSame(MessengerMessageDirection::Outbound, $message->direction);
            $this->assertSame(MessengerMessageStatus::Sent, $message->status);
            $this->assertSame('Reply from CRM', $message->body);

            $conversation->refresh();
            $this->assertSame('Reply from CRM', $conversation->last_message_preview);
            $this->assertNotNull($conversation->last_outbound_message_at);

            $page->refresh();
            $this->assertNotNull($page->last_outbound_at);

            Http::assertSent(function ($request) {
                return str_contains($request->url(), '/page-123/messages')
                    && $request['messaging_type'] === 'RESPONSE'
                    && $request['message']['text'] === 'Reply from CRM'
                    && ! str_contains(json_encode($request->data()), 'secret-page-token');
            });
        });
    }

    public function test_send_outside_24h_window_is_blocked_before_graph_call(): void
    {
        Http::fake();

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            [, $conversation] = $this->seedPageAndConversation(
                windowExpiresAt: now()->subMinute(),
            );

            try {
                app(SendMessengerTextMessageAction::class)->execute($conversation, 'Too late');
                $this->fail('Expected RuntimeException');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('24 hours', $exception->getMessage());
            }

            $this->assertSame(0, MessengerMessage::query()->count());
            Http::assertNothingSent();
        });
    }

    public function test_inactive_disabled_and_reconnect_required_pages_block_sending(): void
    {
        Http::fake();

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            foreach ([
                ['status' => MessengerPageStatus::Disabled, 'is_active' => true],
                ['status' => MessengerPageStatus::ReconnectRequired, 'is_active' => true],
                ['status' => MessengerPageStatus::Failed, 'is_active' => true],
                ['status' => MessengerPageStatus::Active, 'is_active' => false],
            ] as $state) {
                [$page, $conversation] = $this->seedPageAndConversation(
                    windowExpiresAt: now()->addHour(),
                    pageId: 'page-'.$state['status']->value.'-'.($state['is_active'] ? '1' : '0'),
                    status: $state['status'],
                    isActive: $state['is_active'],
                );

                $result = app(MessengerSendingPolicyService::class)->canSendText($page, $conversation);

                $this->assertFalse($result->allowed, 'Expected block for '.$state['status']->value);
                $this->assertStringContainsString('inactive', strtolower((string) $result->reason));
            }

            Http::assertNothingSent();
        });
    }

    public function test_graph_auth_error_marks_page_reconnect_required(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token.',
                    'type' => 'OAuthException',
                    'code' => 190,
                ],
            ], 401),
        ]);

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            [$page, $conversation] = $this->seedPageAndConversation(
                windowExpiresAt: now()->addHour(),
            );

            try {
                app(SendMessengerTextMessageAction::class)->execute($conversation, 'Will fail auth');
                $this->fail('Expected RuntimeException');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('Invalid OAuth', $exception->getMessage());
            }

            $page->refresh();
            $this->assertSame(MessengerPageStatus::ReconnectRequired, $page->status);
            $this->assertNotNull($page->reconnect_required_at);
            $this->assertSame('Invalid OAuth access token.', $page->last_error_message);

            $failed = MessengerMessage::query()->first();
            $this->assertNotNull($failed);
            $this->assertSame(MessengerMessageStatus::Failed, $failed->status);
            $this->assertSame('190', $failed->error_code);

            $registry = MessengerPageRegistry::query()->where('page_id', $page->page_id)->first();
            $this->assertNotNull($registry);
            $this->assertSame(MessengerPageStatus::ReconnectRequired, $registry->status);
            $this->assertArrayNotHasKey('page_access_token', $registry->getAttributes());
        });
    }

    public function test_page_token_stays_encrypted_in_tenant_db_and_absent_from_registry(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $page = MessengerPage::query()->create([
                'page_id' => 'page-token-check',
                'page_name' => 'Token Page',
                'page_access_token' => 'secret-page-token',
            ]);

            $this->assertSame('secret-page-token', $page->page_access_token);
            $this->assertNotSame('secret-page-token', $page->getAttributes()['page_access_token'] ?? null);
            $this->assertArrayNotHasKey('page_access_token', $page->toArray());

            $registry = MessengerPageRegistry::query()->where('page_id', 'page-token-check')->first();
            $this->assertNotNull($registry);
            $this->assertArrayNotHasKey('page_access_token', $registry->getAttributes());
            $this->assertFalse(array_key_exists('page_access_token', $registry->toArray()));
        });
    }

    public function test_auth_error_helper_detects_meta_oauth_codes(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => ['code' => 190, 'message' => 'Expired'],
            ], 400),
        ]);

        $service = app(MessengerGraphApiService::class);
        $response = Http::get('https://graph.facebook.com/v21.0/me');

        $this->assertTrue($service->isAuthenticationError($response));
        $this->assertSame('Expired', $service->safeErrorMessage($response));
    }

    /**
     * @return array{0: MessengerPage, 1: MessengerConversation}
     */
    protected function seedPageAndConversation(
        mixed $windowExpiresAt,
        string $pageId = 'page-123',
        MessengerPageStatus $status = MessengerPageStatus::Active,
        bool $isActive = true,
    ): array {
        $page = MessengerPage::query()->create([
            'page_id' => $pageId,
            'page_name' => 'Store Page',
            'page_access_token' => 'secret-page-token',
            'status' => $status,
            'is_active' => $isActive,
        ]);

        $conversation = MessengerConversation::query()->create([
            'messenger_page_id' => $page->id,
            'sender_psid' => 'psid-456',
            'status' => 'open',
            'customer_service_window_expires_at' => $windowExpiresAt,
            'last_customer_message_at' => now()->subHour(),
        ]);

        return [$page, $conversation];
    }
}
