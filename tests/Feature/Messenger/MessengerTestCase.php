<?php

namespace Tests\Feature\Messenger;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class MessengerTestCase extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'messenger.webhook_verify_token' => 'messenger-test-verify-token',
            'messenger.allow_unsigned_webhooks' => true,
            'messenger.app_secret' => null,
        ]);
    }

    protected function createTenantWithDatabase(): Tenant
    {
        $tenant = Tenant::query()->create([
            'id' => (string) str()->uuid(),
            'name' => 'Messenger Test Store',
            'email' => 'messenger-test@example.com',
            'is_active' => true,
        ]);

        $tenant->domains()->create(['domain' => 'messenger-test-'.$tenant->id.'.localhost']);

        return $tenant->fresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function inboundTextPayload(
        string $pageId = 'page-123',
        string $psid = 'psid-456',
        string $mid = 'mid.TEST123',
        string $text = 'Hello',
        array $overrides = [],
    ): array {
        return array_replace_recursive([
            'object' => 'page',
            'entry' => [[
                'id' => $pageId,
                'time' => now()->getTimestampMs(),
                'messaging' => [[
                    'sender' => ['id' => $psid],
                    'recipient' => ['id' => $pageId],
                    'timestamp' => now()->getTimestampMs(),
                    'message' => [
                        'mid' => $mid,
                        'text' => $text,
                    ],
                ]],
            ]],
        ], $overrides);
    }
}
