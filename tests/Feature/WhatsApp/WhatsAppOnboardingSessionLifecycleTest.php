<?php

namespace Tests\Feature\WhatsApp;

use App\Models\WhatsAppOnboardingSession;
use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;
use App\WhatsApp\Onboarding\WhatsAppOnboardingSessionCleanup;
use App\WhatsApp\Onboarding\WhatsAppOnboardingStateService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class WhatsAppOnboardingSessionLifecycleTest extends WhatsAppTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.embedded_signup.config_id' => '1760158035346145',
            'whatsapp.embedded_signup.central_domain' => 'localhost',
            'whatsapp.embedded_signup.enforce_central_domain' => true,
            'whatsapp.meta_app_id' => 'meta-app-test',
            'whatsapp.app_secret' => 'meta-app-secret',
            'whatsapp.onboarding.session_retention_days' => 7,
            'app.url' => 'http://localhost',
        ]);
    }

    public function test_successful_completion_sets_completed_at(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'access_token' => 'business-token-secret',
                'token_type' => 'bearer',
            ], 200),
        ]);

        $tenant = $this->createTenantWithDatabase();
        $token = $this->issueState((string) $tenant->getTenantKey());

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'success',
            'code' => 'exchange-code-1',
            'session' => [
                'waba_id' => 'waba-life-1',
                'phone_number_id' => 'phone-life-1',
                'event' => 'FINISH',
            ],
        ])->assertOk();

        $state = app(WhatsAppOnboardingStateService::class)->parse($token);
        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();

        $this->assertNotNull($session->completed_at);
        $this->assertNull($session->failed_at);
        $this->assertSame(WhatsAppOnboardingStatus::SubscribingWebhooks->value, $session->status);
        $this->assertSame('********', $session->masked_access_token);
        $this->assertArrayNotHasKey('access_token', $session->toArray());
    }

    public function test_failed_and_cancelled_sessions_set_failed_at(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Bad code', 'code' => 100],
            ], 400),
        ]);

        $tenant = $this->createTenantWithDatabase();
        $failToken = $this->issueState((string) $tenant->getTenantKey());

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $failToken,
            'outcome' => 'success',
            'code' => 'bad',
            'session' => ['waba_id' => 'waba-x', 'phone_number_id' => 'phone-x'],
        ])->assertOk()->assertJsonPath('status', 'failed');

        $failState = app(WhatsAppOnboardingStateService::class)->parse($failToken);
        $failed = WhatsAppOnboardingSession::query()->where('nonce', $failState->nonce)->first();
        $this->assertNotNull($failed->failed_at);
        $this->assertNull($failed->completed_at);
        $this->assertNull($failed->access_token);

        $cancelToken = $this->issueState('tenant-cancel-life');
        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $cancelToken,
            'outcome' => 'cancelled',
        ])->assertOk()->assertJsonPath('status', 'cancelled');

        $cancelState = app(WhatsAppOnboardingStateService::class)->parse($cancelToken);
        $cancelled = WhatsAppOnboardingSession::query()->where('nonce', $cancelState->nonce)->first();
        $this->assertSame('cancelled', $cancelled->status);
        $this->assertNotNull($cancelled->failed_at);
        $this->assertNull($cancelled->completed_at);
    }

    public function test_cleanup_deletes_old_terminal_and_expired_but_keeps_active(): void
    {
        WhatsAppOnboardingSession::query()->delete();

        $active = WhatsAppOnboardingSession::query()->create([
            'nonce' => 'nonce-active',
            'tenant_id' => 'tenant-a',
            'connection_method' => WhatsAppConnectionMethod::EmbeddedSignupApiOnly->value,
            'status' => WhatsAppOnboardingStatus::InProgress->value,
            'expires_at' => now()->addHour(),
            'completed_at' => null,
            'failed_at' => null,
        ]);

        $oldCompleted = WhatsAppOnboardingSession::query()->create([
            'nonce' => 'nonce-completed',
            'tenant_id' => 'tenant-a',
            'connection_method' => WhatsAppConnectionMethod::EmbeddedSignupApiOnly->value,
            'status' => WhatsAppOnboardingStatus::SubscribingWebhooks->value,
            'expires_at' => now()->subDays(10),
            'completed_at' => now()->subDays(10),
            'failed_at' => null,
            'access_token' => 'old-secret',
        ]);

        $oldFailed = WhatsAppOnboardingSession::query()->create([
            'nonce' => 'nonce-failed',
            'tenant_id' => 'tenant-a',
            'connection_method' => WhatsAppConnectionMethod::EmbeddedSignupApiOnly->value,
            'status' => 'failed',
            'expires_at' => now()->subDays(10),
            'completed_at' => null,
            'failed_at' => now()->subDays(10),
        ]);

        $oldExpired = WhatsAppOnboardingSession::query()->create([
            'nonce' => 'nonce-expired',
            'tenant_id' => 'tenant-a',
            'connection_method' => WhatsAppConnectionMethod::EmbeddedSignupApiOnly->value,
            'status' => WhatsAppOnboardingStatus::InProgress->value,
            'expires_at' => now()->subDays(10),
            'completed_at' => null,
            'failed_at' => null,
        ]);

        $deleted = app(WhatsAppOnboardingSessionCleanup::class)->run(7);

        $this->assertSame(3, $deleted);
        $this->assertTrue(WhatsAppOnboardingSession::query()->whereKey($active->id)->exists());
        $this->assertFalse(WhatsAppOnboardingSession::query()->whereKey($oldCompleted->id)->exists());
        $this->assertFalse(WhatsAppOnboardingSession::query()->whereKey($oldFailed->id)->exists());
        $this->assertFalse(WhatsAppOnboardingSession::query()->whereKey($oldExpired->id)->exists());
    }

    public function test_cleanup_command_dry_run_and_delete(): void
    {
        WhatsAppOnboardingSession::query()->delete();

        WhatsAppOnboardingSession::query()->create([
            'nonce' => 'nonce-cmd',
            'tenant_id' => 'tenant-cmd',
            'connection_method' => WhatsAppConnectionMethod::EmbeddedSignupApiOnly->value,
            'status' => 'cancelled',
            'expires_at' => now()->subDays(20),
            'failed_at' => now()->subDays(20),
        ]);

        Artisan::call('whatsapp:onboarding-sessions:cleanup', ['--dry-run' => true, '--days' => 7]);
        $this->assertStringContainsString('Dry run: 1', Artisan::output());
        $this->assertSame(1, WhatsAppOnboardingSession::query()->count());

        Artisan::call('whatsapp:onboarding-sessions:cleanup', ['--days' => 7]);
        $this->assertSame(0, WhatsAppOnboardingSession::query()->count());
    }

    protected function issueState(string $tenantId): string
    {
        return app(WhatsAppOnboardingStateService::class)->issue(
            tenantId: $tenantId,
            connectionMethod: WhatsAppConnectionMethod::EmbeddedSignupApiOnly,
            returnUrl: 'http://localhost/app/whatsapp-numbers',
        );
    }
}
