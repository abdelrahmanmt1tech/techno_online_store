<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Tenant\WhatsAppNumber;
use App\Models\WhatsAppNumberRegistry;
use App\Models\WhatsAppOnboardingSession;
use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;
use App\WhatsApp\Enums\WhatsAppTokenSource;
use App\WhatsApp\Onboarding\WhatsAppOnboardingStateService;
use Illuminate\Support\Facades\Http;

class WhatsAppOnboardingPhaseCTest extends WhatsAppTestCase
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

    public function test_start_page_requires_valid_signed_state_and_shows_launch_ui(): void
    {
        $token = $this->issueState('tenant-start');

        $this->get('/whatsapp/onboarding/start?state='.urlencode($token))
            ->assertOk()
            ->assertSee(__('dashboard.whatsapp_connect_via_meta'))
            ->assertSee(__('dashboard.whatsapp_onboarding_launch_cta'))
            ->assertSee('1760158035346145')
            ->assertSee('FB.init', false);

        $this->get('/whatsapp/onboarding/start?state=tampered')
            ->assertForbidden();
    }

    public function test_complete_rejects_raw_tenant_id_and_invalid_state(): void
    {
        $this->postJson('/whatsapp/onboarding/complete', [
            'tenant_id' => 'forged-tenant',
        ])->assertForbidden();

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => 'not-valid',
            'outcome' => 'success',
            'code' => 'code-1',
        ])->assertForbidden();
    }

    public function test_successful_token_exchange_stores_encrypted_token_on_tenant_number_not_registry(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'access_token' => 'business-token-secret',
                'token_type' => 'bearer',
            ], 200),
        ]);

        $tenant = $this->createTenantWithDatabase();
        $token = $this->issueState((string) $tenant->getTenantKey());

        $response = $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'success',
            'code' => 'exchange-code-1',
            'session' => [
                'waba_id' => 'waba-100',
                'phone_number_id' => 'phone-100',
                'business_id' => 'biz-100',
                'event' => 'FINISH',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', WhatsAppOnboardingStatus::SubscribingWebhooks->value);

        $state = app(WhatsAppOnboardingStateService::class)->parse($token);
        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();
        $this->assertNotNull($session);
        $this->assertSame('business-token-secret', $session->access_token);
        $this->assertSame('********', $session->masked_access_token);
        $this->assertArrayNotHasKey('access_token', $session->toArray());

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->where('phone_number_id', 'phone-100')->first();
            $this->assertNotNull($number);
            $this->assertSame('business-token-secret', $number->access_token);
            $this->assertSame(WhatsAppConnectionMethod::EmbeddedSignupApiOnly, $number->connection_method);
            $this->assertSame(WhatsAppTokenSource::EmbeddedSignup, $number->token_source);
            $this->assertSame(WhatsAppOnboardingStatus::SubscribingWebhooks, $number->onboarding_status);
            $this->assertArrayNotHasKey('access_token', $number->toArray());
        });

        $registry = WhatsAppNumberRegistry::query()->where('phone_number_id', 'phone-100')->first();
        $this->assertNotNull($registry);
        $this->assertSame((string) $tenant->getTenantKey(), $registry->tenant_id);
        $this->assertArrayNotHasKey('access_token', $registry->getAttributes());
        $this->assertFalse(array_key_exists('access_token', $registry->toArray()));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/oauth/access_token')
                && $request['client_id'] === 'meta-app-test'
                && $request['code'] === 'exchange-code-1'
                && ! str_contains(json_encode($request->data()), 'business-token-secret');
        });
    }

    public function test_failed_token_exchange_marks_session_failed_without_storing_token(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid verification code format.',
                    'type' => 'OAuthException',
                    'code' => 100,
                ],
            ], 400),
        ]);

        $tenant = $this->createTenantWithDatabase();
        $token = $this->issueState((string) $tenant->getTenantKey());

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'success',
            'code' => 'bad-code',
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
        $this->assertNull($session->completed_at);
        $this->assertStringContainsString('Invalid verification code', (string) $session->last_error);

        $tenant->run(function () {
            $this->assertSame(0, WhatsAppNumber::query()->count());
        });
    }

    public function test_cancelled_outcome_does_not_call_meta_and_marks_cancelled(): void
    {
        Http::fake();

        $token = $this->issueState('tenant-cancel');

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'cancelled',
            'session' => ['event' => 'CANCEL', 'current_step' => 'PHONE_NUMBER_SETUP'],
        ])->assertOk()->assertJsonPath('status', 'cancelled');

        Http::assertNothingSent();

        $state = app(WhatsAppOnboardingStateService::class)->parse($token);
        $this->assertSame(
            'cancelled',
            WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->value('status'),
        );
    }

    public function test_status_page_shows_session_result_and_central_domain_still_enforced(): void
    {
        $token = $this->issueState('tenant-status');
        $state = app(WhatsAppOnboardingStateService::class)->parse($token);

        WhatsAppOnboardingSession::query()->create([
            'nonce' => $state->nonce,
            'tenant_id' => 'tenant-status',
            'connection_method' => WhatsAppConnectionMethod::EmbeddedSignupApiOnly->value,
            'status' => 'cancelled',
            'return_url' => 'http://localhost/app/whatsapp-numbers',
        ]);

        $this->get('/whatsapp/onboarding/status?state='.urlencode($token))
            ->assertOk()
            ->assertSee(__('dashboard.whatsapp_onboarding_result_cancelled'));

        config([
            'whatsapp.embedded_signup.central_domain' => 'online-store.technomasrsystems.com',
        ]);

        $this->get('/whatsapp/onboarding/status?state='.urlencode($token))
            ->assertForbidden();
    }

    public function test_manual_number_create_still_works_after_phase_c(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201111111111',
                'phone_number_id' => 'manual-phone-1',
                'whatsapp_business_account_id' => 'manual-waba-1',
                'access_token' => 'manual-token',
                'status' => 'active',
                'is_active' => true,
            ]);

            $this->assertSame(WhatsAppConnectionMethod::ManualApiOnly, $number->connection_method);
            $this->assertSame(WhatsAppOnboardingStatus::Completed, $number->onboarding_status);
            $this->assertSame(WhatsAppTokenSource::Manual, $number->token_source);
            $this->assertSame('manual-token', $number->access_token);
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
