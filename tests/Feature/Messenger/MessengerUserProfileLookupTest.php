<?php

namespace Tests\Feature\Messenger;

use App\Messenger\Actions\ProcessInboundMessengerMessageAction;
use App\Messenger\Jobs\ProcessMessengerWebhookJob;
use App\Messenger\Services\MessengerWebhookPayloadRedactor;
use App\Messenger\Services\MessengerWebhookResolver;
use App\Models\MessengerWebhookEvent;
use App\Models\Tenant\MessengerContact;
use App\Models\Tenant\MessengerConversation;
use App\Models\Tenant\MessengerMessage;
use App\Models\Tenant\MessengerPage;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

class MessengerUserProfileLookupTest extends MessengerTestCase
{
    public function test_inbound_message_with_successful_profile_fetch_updates_contact_name(): void
    {
        $longProfilePic = 'https://scontent.xx.fbcdn.net/v/t1.30497-1/'.str_repeat('a', 300).'/picture?stp=dst-jpg_s480x480&_nc_cat=1&ccb=1-7&_nc_sid=xyz&_nc_ohc=abc&_nc_ht=scontent.xx.fbcdn.net&oh='.str_repeat('f', 80).'&oe=ABCDEF01';

        $this->assertGreaterThan(255, strlen($longProfilePic));

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'first_name' => 'Ahmed',
                'last_name' => 'Hassan',
                'name' => 'Ahmed Hassan',
                'profile_pic' => $longProfilePic,
            ], 200),
        ]);

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-123',
                'page_name' => 'Store Page',
                'page_access_token' => 'secret-page-token',
            ]);
        });

        $this->postJson('/webhooks/meta/messenger', $this->inboundTextPayload())->assertOk();

        (new ProcessMessengerWebhookJob(MessengerWebhookEvent::query()->latest('id')->first()->id))->handle(
            app(MessengerWebhookResolver::class),
            app(ProcessInboundMessengerMessageAction::class),
            app(MessengerWebhookPayloadRedactor::class),
        );

        $tenant->run(function () use ($longProfilePic) {
            $contact = MessengerContact::query()->where('psid', 'psid-456')->first();
            $this->assertNotNull($contact);
            $this->assertSame('Ahmed Hassan', $contact->profile_name);
            $this->assertSame($longProfilePic, $contact->profile_picture_url);

            $conversation = MessengerConversation::query()->first();
            $this->assertSame('Ahmed Hassan', $conversation->customer_name);
            $this->assertSame(1, MessengerMessage::query()->count());
        });

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_contains($request->url(), '/psid-456')
                && ! str_contains($request->url(), 'secret-page-token');
        });
    }

    public function test_profile_fetch_failure_still_processes_message_with_psid_fallback(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => '(#100) No profile available',
                    'code' => 100,
                ],
            ], 400),
        ]);

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-123',
                'page_name' => 'Store Page',
                'page_access_token' => 'secret-page-token',
            ]);
        });

        $this->postJson('/webhooks/meta/messenger', $this->inboundTextPayload(
            mid: 'mid.PROFILE.FAIL',
        ))->assertOk();

        (new ProcessMessengerWebhookJob(MessengerWebhookEvent::query()->latest('id')->first()->id))->handle(
            app(MessengerWebhookResolver::class),
            app(ProcessInboundMessengerMessageAction::class),
            app(MessengerWebhookPayloadRedactor::class),
        );

        $tenant->run(function () {
            $this->assertSame(1, MessengerMessage::query()->where('provider_message_id', 'mid.PROFILE.FAIL')->count());

            $contact = MessengerContact::query()->where('psid', 'psid-456')->first();
            $this->assertNotNull($contact);
            $this->assertNull($contact->profile_name);

            $conversation = MessengerConversation::query()->first();
            $this->assertNull($conversation->customer_name);
            $this->assertSame('psid-456', $conversation->sender_psid);
        });
    }

    public function test_profile_lookup_logs_do_not_contain_page_access_token(): void
    {
        $loggedPayloads = [];

        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$loggedPayloads): void {
            $loggedPayloads[] = json_encode([
                'message' => $event->message,
                'context' => $event->context,
            ], JSON_UNESCAPED_UNICODE);
        });

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Denied', 'code' => 10],
            ], 403),
        ]);

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $page = MessengerPage::query()->create([
                'page_id' => 'page-log',
                'page_name' => 'Log Page',
                'page_access_token' => 'secret-page-token-must-not-appear',
            ]);

            app(ProcessInboundMessengerMessageAction::class)->execute($page, [
                'sender' => ['id' => 'psid-log'],
                'recipient' => ['id' => 'page-log'],
                'timestamp' => now()->getTimestampMs(),
                'message' => [
                    'mid' => 'mid.LOG1',
                    'text' => 'Hi',
                ],
            ]);
        });

        $this->assertNotEmpty($loggedPayloads);

        foreach ($loggedPayloads as $payload) {
            $this->assertStringNotContainsString('secret-page-token-must-not-appear', $payload);
        }

        Http::assertSent(function ($request) {
            return ! str_contains($request->url(), 'secret-page-token-must-not-appear');
        });
    }
}
