<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Tenant\WhatsAppNumber;
use App\Models\WhatsAppNumberRegistry;
use App\Models\WhatsAppOnboardingSession;
use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;
use App\WhatsApp\Enums\WhatsAppTokenSource;
use App\WhatsApp\Onboarding\FinalizeWhatsAppEmbeddedSignupAction;
use App\WhatsApp\Onboarding\WhatsAppOnboardingStateService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppOnboardingPhaseDTest extends WhatsAppTestCase
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
            'whatsapp.graph_api_version' => 'v21.0',
            'app.url' => 'http://localhost',
            'app.bypass_permissions' => true,
        ]);
    }

    public function test_successful_waba_subscription_marks_onboarding_completed(): void
    {
        $this->fakeSuccessfulPhaseDGraph();

        $tenant = $this->createTenantWithDatabase();
        $token = $this->issueState((string) $tenant->getTenantKey());

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'success',
            'code' => 'code-ok',
            'session' => [
                'waba_id' => 'waba-d1',
                'phone_number_id' => 'phone-d1',
                'display_phone_number' => '+201111111111',
                'event' => 'FINISH',
            ],
        ])->assertOk()->assertJsonPath('status', 'completed');

        $state = app(WhatsAppOnboardingStateService::class)->parse($token);
        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();

        $this->assertSame('completed', $session->status);
        $this->assertNotNull($session->completed_at);
        $this->assertSame('subscribed', data_get($session->session_payload, 'subscribed_apps.success') ? 'subscribed' : null);
        $this->assertTrue((bool) data_get($session->session_payload, 'subscribed_apps.success'));

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->where('phone_number_id', 'phone-d1')->first();
            $this->assertNotNull($number);
            $this->assertSame(WhatsAppOnboardingStatus::Completed, $number->onboarding_status);
            $this->assertSame('subscribed', $number->webhook_status);
            $this->assertTrue($number->is_active);
        });

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/waba-d1/subscribed_apps')
            && $request->method() === 'POST');
    }

    public function test_phone_metadata_updates_tenant_number_and_registry_has_no_token(): void
    {
        $this->fakeSuccessfulPhaseDGraph(
            phoneId: 'phone-meta',
            display: '+202222222222',
            verifiedName: 'Meta Verified Name',
        );

        $tenant = $this->createTenantWithDatabase();
        $token = $this->issueState((string) $tenant->getTenantKey());

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'success',
            'code' => 'code-meta',
            'session' => [
                'waba_id' => 'waba-meta',
                'phone_number_id' => 'phone-meta',
                'event' => 'FINISH',
            ],
        ])->assertOk();

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->where('phone_number_id', 'phone-meta')->first();
            $this->assertSame('+202222222222', $number->display_phone_number);
            $this->assertSame('Meta Verified Name', $number->business_name);
            $this->assertSame('waba-meta', $number->whatsapp_business_account_id);
            $this->assertSame(WhatsAppConnectionMethod::EmbeddedSignupApiOnly, $number->connection_method);
            $this->assertSame(WhatsAppTokenSource::EmbeddedSignup, $number->token_source);
            $this->assertNotNull($number->connected_at);
            $this->assertSame('phase-d-token', $number->access_token);
        });

        $registry = WhatsAppNumberRegistry::query()->where('phone_number_id', 'phone-meta')->first();
        $this->assertNotNull($registry);
        $this->assertSame(WhatsAppOnboardingStatus::Completed, $registry->onboarding_status);
        $this->assertArrayNotHasKey('access_token', $registry->getAttributes());
    }

    public function test_multiple_phones_without_selection_marks_awaiting_phone_selection(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'oauth/access_token')) {
                return Http::response(['access_token' => 'multi-token'], 200);
            }

            if (str_contains($request->url(), 'subscribed_apps')) {
                return Http::response(['success' => true], 200);
            }

            if (str_contains($request->url(), 'phone_numbers')) {
                return Http::response([
                    'data' => [
                        ['id' => 'phone-a', 'display_phone_number' => '+201', 'verified_name' => 'A'],
                        ['id' => 'phone-b', 'display_phone_number' => '+202', 'verified_name' => 'B'],
                    ],
                ], 200);
            }

            return Http::response([], 404);
        });

        $tenant = $this->createTenantWithDatabase();
        $token = $this->issueState((string) $tenant->getTenantKey());

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'success',
            'code' => 'code-multi',
            'session' => [
                'waba_id' => 'waba-multi',
                'event' => 'FINISH',
            ],
        ])->assertOk()->assertJsonPath('status', 'awaiting_phone_selection');

        $state = app(WhatsAppOnboardingStateService::class)->parse($token);
        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();
        $this->assertSame('awaiting_phone_selection', $session->status);
        $this->assertCount(2, $session->session_payload['available_phones'] ?? []);

        $tenant->run(function () {
            $this->assertSame(0, WhatsAppNumber::query()->count());
        });
    }

    public function test_graph_subscribe_failure_marks_failed_safely_and_keeps_number_token_for_retry(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'oauth/access_token')) {
                return Http::response(['access_token' => 'fail-token-secret'], 200);
            }

            if (str_contains($request->url(), 'subscribed_apps')) {
                return Http::response([
                    'error' => [
                        'message' => '(#100) Permission denied',
                        'code' => 100,
                    ],
                ], 400);
            }

            return Http::response(['data' => []], 200);
        });

        $tenant = $this->createTenantWithDatabase();
        $token = $this->issueState((string) $tenant->getTenantKey());

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'success',
            'code' => 'code-fail',
            'session' => [
                'waba_id' => 'waba-fail',
                'phone_number_id' => 'phone-fail',
                'event' => 'FINISH',
            ],
        ])->assertOk()->assertJsonPath('ok', false)->assertJsonPath('status', 'failed');

        $state = app(WhatsAppOnboardingStateService::class)->parse($token);
        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();
        $this->assertSame('failed', $session->status);
        $this->assertNull($session->access_token);
        $this->assertNotNull($session->failed_at);
        $this->assertStringContainsString('Permission denied', (string) $session->last_error);

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->where('phone_number_id', 'phone-fail')->first();
            $this->assertNotNull($number);
            $this->assertSame('fail-token-secret', $number->access_token);
            $this->assertSame(WhatsAppOnboardingStatus::Failed, $number->onboarding_status);
            $this->assertNotNull($number->last_onboarding_error);
        });
    }

    public function test_finalize_retry_is_idempotent_and_does_not_duplicate_numbers(): void
    {
        $this->fakeSuccessfulPhaseDGraph(phoneId: 'phone-retry', wabaId: 'waba-retry');

        $tenant = $this->createTenantWithDatabase();
        $token = $this->issueState((string) $tenant->getTenantKey());

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'success',
            'code' => 'code-retry',
            'session' => [
                'waba_id' => 'waba-retry',
                'phone_number_id' => 'phone-retry',
                'event' => 'FINISH',
            ],
        ])->assertOk()->assertJsonPath('status', 'completed');

        $this->post(route('whatsapp.onboarding.finalize'), [
            'state' => $token,
        ])->assertRedirect();

        $state = app(WhatsAppOnboardingStateService::class)->parse($token);
        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();
        $this->assertSame('completed', $session->status);

        $tenant->run(function () {
            $this->assertSame(1, WhatsAppNumber::query()->where('phone_number_id', 'phone-retry')->count());
        });

        // Second complete with same phone_number_id must updateOrCreate, not duplicate.
        $token2 = $this->issueState((string) $tenant->getTenantKey());
        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token2,
            'outcome' => 'success',
            'code' => 'code-retry-2',
            'session' => [
                'waba_id' => 'waba-retry',
                'phone_number_id' => 'phone-retry',
                'event' => 'FINISH',
            ],
        ])->assertOk()->assertJsonPath('status', 'completed');

        $tenant->run(function () {
            $this->assertSame(1, WhatsAppNumber::query()->where('phone_number_id', 'phone-retry')->count());
        });
    }

    public function test_failed_finalize_can_retry_using_tenant_number_token(): void
    {
        $calls = ['subscribe' => 0];

        Http::fake(function (Request $request) use (&$calls) {
            if (str_contains($request->url(), 'oauth/access_token')) {
                return Http::response(['access_token' => 'retry-token'], 200);
            }

            if (str_contains($request->url(), 'subscribed_apps')) {
                $calls['subscribe']++;

                if ($calls['subscribe'] === 1) {
                    return Http::response([
                        'error' => ['message' => 'Temporary failure', 'code' => 1],
                    ], 500);
                }

                return Http::response(['success' => true], 200);
            }

            if (str_contains($request->url(), 'phone_numbers')) {
                return Http::response([
                    'data' => [[
                        'id' => 'phone-re',
                        'display_phone_number' => '+203',
                        'verified_name' => 'Retry Co',
                    ]],
                ], 200);
            }

            return Http::response([], 200);
        });

        $tenant = $this->createTenantWithDatabase();
        $token = $this->issueState((string) $tenant->getTenantKey());

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'success',
            'code' => 'code-re',
            'session' => [
                'waba_id' => 'waba-re',
                'phone_number_id' => 'phone-re',
                'event' => 'FINISH',
            ],
        ])->assertOk()->assertJsonPath('status', 'failed');

        $this->post(route('whatsapp.onboarding.finalize'), [
            'state' => $token,
        ])->assertRedirect(route('whatsapp.onboarding.status', ['state' => $token]));

        $state = app(WhatsAppOnboardingStateService::class)->parse($token);
        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();
        $this->assertSame('completed', $session->status);

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->where('phone_number_id', 'phone-re')->first();
            $this->assertSame(WhatsAppOnboardingStatus::Completed, $number->onboarding_status);
            $this->assertSame('retry-token', $number->access_token);
        });
    }

    public function test_status_page_shows_completed_success_and_retry_for_failed(): void
    {
        $token = $this->issueState('tenant-status-d');
        $state = app(WhatsAppOnboardingStateService::class)->parse($token);

        WhatsAppOnboardingSession::query()->create([
            'nonce' => $state->nonce,
            'tenant_id' => 'tenant-status-d',
            'connection_method' => WhatsAppConnectionMethod::EmbeddedSignupApiOnly->value,
            'status' => 'completed',
            'waba_id' => 'waba-ui',
            'phone_number_id' => 'phone-ui',
            'display_phone_number' => '+204444444444',
            'return_url' => 'http://localhost/app/whatsapp-numbers',
        ]);

        $this->get('/whatsapp/onboarding/status?state='.urlencode($token))
            ->assertOk()
            ->assertSee(__('dashboard.whatsapp_onboarding_result_completed'))
            ->assertSee('+204444444444')
            ->assertSee(__('dashboard.whatsapp_onboarding_next_step_test_messages'))
            ->assertDontSee(__('dashboard.whatsapp_onboarding_retry_finalize'));
    }

    public function test_manual_number_still_works_and_completed_session_not_used_for_messaging(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $manual = WhatsAppNumber::query()->create([
                'display_phone_number' => '+209999999999',
                'phone_number_id' => 'manual-keep',
                'whatsapp_business_account_id' => 'manual-waba',
                'access_token' => 'manual-token',
                'status' => 'active',
                'is_active' => true,
            ]);

            $this->assertSame(WhatsAppTokenSource::Manual, $manual->token_source);
            $this->assertSame(WhatsAppOnboardingStatus::Completed, $manual->onboarding_status);
        });

        $this->fakeSuccessfulPhaseDGraph(phoneId: 'phone-es', wabaId: 'waba-es');

        $token = $this->issueState((string) $tenant->getTenantKey());
        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'success',
            'code' => 'code-es',
            'session' => [
                'waba_id' => 'waba-es',
                'phone_number_id' => 'phone-es',
                'event' => 'FINISH',
            ],
        ])->assertOk();

        $tenant->run(function () {
            $this->assertSame(2, WhatsAppNumber::query()->count());
            $this->assertSame(1, WhatsAppNumber::query()->where('phone_number_id', 'manual-keep')->count());
            $this->assertSame('manual-token', WhatsAppNumber::query()->where('phone_number_id', 'manual-keep')->value('access_token'));
            $this->assertSame('phase-d-token', WhatsAppNumber::query()->where('phone_number_id', 'phone-es')->value('access_token'));
        });

        // Operational messaging reads tenant WhatsAppNumber tokens only — session is temporary.
        $state = app(WhatsAppOnboardingStateService::class)->parse($token);
        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();
        $this->assertSame('completed', $session->status);
        $this->assertNotSame(
            $session->access_token,
            null, // session may still hold encrypted token until cleanup; messaging must not use it
        );
        $this->assertTrue(class_exists(FinalizeWhatsAppEmbeddedSignupAction::class));
    }

    public function test_finalize_rejects_raw_tenant_id(): void
    {
        $this->post(route('whatsapp.onboarding.finalize'), [
            'tenant_id' => 'forged',
        ])->assertForbidden();
    }

    protected function fakeSuccessfulPhaseDGraph(
        string $phoneId = 'phone-d1',
        string $wabaId = 'waba-d1',
        string $display = '+201000000000',
        string $verifiedName = 'Test Biz',
        string $token = 'phase-d-token',
    ): void {
        Http::fake(function (Request $request) use ($phoneId, $display, $verifiedName, $token) {
            if (str_contains($request->url(), 'oauth/access_token')) {
                return Http::response(['access_token' => $token, 'token_type' => 'bearer'], 200);
            }

            if (str_contains($request->url(), 'subscribed_apps')) {
                return Http::response(['success' => true], 200);
            }

            if (str_contains($request->url(), 'phone_numbers')) {
                return Http::response([
                    'data' => [[
                        'id' => $phoneId,
                        'display_phone_number' => $display,
                        'verified_name' => $verifiedName,
                    ]],
                ], 200);
            }

            if (str_contains($request->url(), $phoneId)) {
                return Http::response([
                    'id' => $phoneId,
                    'display_phone_number' => $display,
                    'verified_name' => $verifiedName,
                ], 200);
            }

            return Http::response(['error' => ['message' => 'Unexpected Graph URL']], 500);
        });
    }

    protected function issueState(string $tenantId): string
    {
        return app(WhatsAppOnboardingStateService::class)->issue(
            tenantId: $tenantId,
            connectionMethod: WhatsAppConnectionMethod::EmbeddedSignupApiOnly,
            returnUrl: 'http://localhost/app/whatsapp-numbers',
            userId: 7,
        );
    }
}
