<?php

namespace Tests\Feature\WhatsApp;

use App\Filament\Tenant\Pages\ConnectWhatsAppPage;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\TenantUser;
use App\Models\WhatsAppNumberRegistry;
use App\Models\WhatsAppOnboardingSession;
use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;
use App\WhatsApp\Enums\WhatsAppTokenSource;
use App\WhatsApp\Onboarding\WhatsAppOnboardingStateService;
use Filament\Facades\Filament;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

class WhatsAppOnboardingPhaseETest extends WhatsAppTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.embedded_signup.config_id' => '1760158035346145',
            'whatsapp.embedded_signup.coexistence_config_id' => 'coexist-config-555',
            'whatsapp.embedded_signup.central_domain' => 'localhost',
            'whatsapp.embedded_signup.enforce_central_domain' => true,
            'whatsapp.meta_app_id' => 'meta-app-test',
            'whatsapp.app_secret' => 'meta-app-secret',
            'whatsapp.graph_api_version' => 'v21.0',
            'app.url' => 'http://localhost',
            'app.bypass_permissions' => true,
        ]);
    }

    public function test_coexistence_start_uses_coexistence_config_id_not_api_only(): void
    {
        $token = $this->issueState('tenant-coexist-start', WhatsAppConnectionMethod::EmbeddedSignupCoexistence);

        $this->get('/whatsapp/onboarding/start?state='.urlencode($token))
            ->assertOk()
            ->assertSee('coexist-config-555')
            ->assertDontSee('1760158035346145')
            ->assertSee('whatsapp_business_app_onboarding', false);

        $apiToken = $this->issueState('tenant-api-start', WhatsAppConnectionMethod::EmbeddedSignupApiOnly);

        $this->get('/whatsapp/onboarding/start?state='.urlencode($apiToken))
            ->assertOk()
            ->assertSee('1760158035346145')
            ->assertDontSee('coexist-config-555');
    }

    public function test_coexistence_start_unavailable_when_coexistence_config_missing(): void
    {
        config(['whatsapp.embedded_signup.coexistence_config_id' => null]);

        $token = $this->issueState('tenant-missing-cfg', WhatsAppConnectionMethod::EmbeddedSignupCoexistence);

        $this->get('/whatsapp/onboarding/start?state='.urlencode($token))
            ->assertOk()
            ->assertSee(__('dashboard.whatsapp_onboarding_missing_coexistence_config'));
    }

    public function test_signed_state_carries_coexistence_method_and_rejects_raw_tenant_id(): void
    {
        $token = $this->issueState('tenant-signed-co', WhatsAppConnectionMethod::EmbeddedSignupCoexistence);
        $state = app(WhatsAppOnboardingStateService::class)->parse($token);
        $this->assertSame(WhatsAppConnectionMethod::EmbeddedSignupCoexistence, $state->connectionMethod);

        $this->get('/whatsapp/onboarding/callback?tenant_id=forged-tenant')
            ->assertForbidden();

        $this->get('/whatsapp/onboarding/start?state=tampered')
            ->assertForbidden();

        $this->postJson('/whatsapp/onboarding/complete', [
            'tenant_id' => 'forged',
            'outcome' => 'success',
            'code' => 'x',
        ])->assertForbidden();
    }

    public function test_successful_coexistence_completion_persists_flags_and_no_registry_token(): void
    {
        $this->fakeSuccessfulGraph(phoneId: 'phone-co-1', wabaId: 'waba-co-1');

        $tenant = $this->createTenantWithDatabase();
        $token = $this->issueState((string) $tenant->getTenantKey(), WhatsAppConnectionMethod::EmbeddedSignupCoexistence);

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'success',
            'code' => 'code-co-1',
            'session' => [
                'waba_id' => 'waba-co-1',
                'phone_number_id' => 'phone-co-1',
                'display_phone_number' => '+201555000111',
                'event' => 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING',
            ],
        ])->assertOk()->assertJsonPath('status', 'completed');

        $state = app(WhatsAppOnboardingStateService::class)->parse($token);
        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();
        $this->assertSame(WhatsAppConnectionMethod::EmbeddedSignupCoexistence->value, $session->connection_method);
        $this->assertSame('coexist-token', $session->access_token);
        $this->assertArrayNotHasKey('access_token', $session->toArray());

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->where('phone_number_id', 'phone-co-1')->first();
            $this->assertNotNull($number);
            $this->assertSame(WhatsAppConnectionMethod::EmbeddedSignupCoexistence, $number->connection_method);
            $this->assertTrue($number->coexistence_enabled);
            $this->assertSame(WhatsAppTokenSource::EmbeddedSignup, $number->token_source);
            $this->assertSame(WhatsAppOnboardingStatus::Completed, $number->onboarding_status);
            $this->assertSame('subscribed', $number->webhook_status);
            $this->assertSame('+201555000111', $number->business_app_number);
            $this->assertSame('coexist-token', $number->access_token);
        });

        $registry = WhatsAppNumberRegistry::query()->where('phone_number_id', 'phone-co-1')->first();
        $this->assertNotNull($registry);
        $this->assertTrue((bool) $registry->coexistence_enabled);
        $this->assertArrayNotHasKey('access_token', $registry->getAttributes());
    }

    public function test_coexistence_does_not_guess_among_multiple_phones(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'oauth/access_token')) {
                return Http::response(['access_token' => 'coexist-token'], 200);
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
        $token = $this->issueState((string) $tenant->getTenantKey(), WhatsAppConnectionMethod::EmbeddedSignupCoexistence);

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'success',
            'code' => 'code-multi',
            'session' => [
                'waba_id' => 'waba-multi',
                'event' => 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING',
            ],
        ])->assertOk()->assertJsonPath('status', 'awaiting_phone_selection');

        $tenant->run(function () {
            $this->assertSame(0, WhatsAppNumber::query()->count());
        });
    }

    public function test_coexistence_incomplete_assets_stay_pending_not_failed(): void
    {
        Http::fake([
            'graph.facebook.com/*oauth/access_token*' => Http::response(['access_token' => 'pending-token'], 200),
        ]);

        $tenant = $this->createTenantWithDatabase();
        $token = $this->issueState((string) $tenant->getTenantKey(), WhatsAppConnectionMethod::EmbeddedSignupCoexistence);

        $this->postJson('/whatsapp/onboarding/complete', [
            'state' => $token,
            'outcome' => 'success',
            'code' => 'code-pending',
            'session' => [
                'event' => 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING',
            ],
        ])->assertOk()->assertJsonPath('status', 'in_progress');

        $state = app(WhatsAppOnboardingStateService::class)->parse($token);
        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();
        $this->assertSame('in_progress', $session->status);
        $this->assertSame('pending-token', $session->access_token);

        $tenant->run(function () {
            $this->assertSame(0, WhatsAppNumber::query()->count());
        });
    }

    public function test_no_duplicate_numbers_and_manual_still_works(): void
    {
        $this->fakeSuccessfulGraph(phoneId: 'phone-dup', wabaId: 'waba-dup');

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            WhatsAppNumber::query()->create([
                'display_phone_number' => '+209999999999',
                'phone_number_id' => 'manual-keep-co',
                'whatsapp_business_account_id' => 'manual-waba',
                'access_token' => 'manual-token',
                'status' => 'active',
                'is_active' => true,
            ]);
        });

        $token = $this->issueState((string) $tenant->getTenantKey(), WhatsAppConnectionMethod::EmbeddedSignupCoexistence);
        $payload = [
            'outcome' => 'success',
            'code' => 'code-dup',
            'session' => [
                'waba_id' => 'waba-dup',
                'phone_number_id' => 'phone-dup',
                'event' => 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING',
            ],
        ];

        $this->postJson('/whatsapp/onboarding/complete', array_merge($payload, ['state' => $token]))
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $token2 = $this->issueState((string) $tenant->getTenantKey(), WhatsAppConnectionMethod::EmbeddedSignupCoexistence);
        $this->postJson('/whatsapp/onboarding/complete', array_merge($payload, [
            'state' => $token2,
            'code' => 'code-dup-2',
        ]))->assertOk()->assertJsonPath('status', 'completed');

        $tenant->run(function () {
            $this->assertSame(1, WhatsAppNumber::query()->where('phone_number_id', 'phone-dup')->count());
            $this->assertSame(1, WhatsAppNumber::query()->where('phone_number_id', 'manual-keep-co')->count());
            $this->assertSame('manual-token', WhatsAppNumber::query()->where('phone_number_id', 'manual-keep-co')->value('access_token'));
        });
    }

    public function test_status_page_shows_coexistence_completed_copy(): void
    {
        $token = $this->issueState('tenant-status-co', WhatsAppConnectionMethod::EmbeddedSignupCoexistence);
        $state = app(WhatsAppOnboardingStateService::class)->parse($token);

        WhatsAppOnboardingSession::query()->create([
            'nonce' => $state->nonce,
            'tenant_id' => 'tenant-status-co',
            'connection_method' => WhatsAppConnectionMethod::EmbeddedSignupCoexistence->value,
            'status' => 'completed',
            'waba_id' => 'waba-ui',
            'phone_number_id' => 'phone-ui',
            'display_phone_number' => '+201555000222',
            'return_url' => 'http://localhost/app/whatsapp-numbers',
        ]);

        $this->get('/whatsapp/onboarding/status?state='.urlencode($token))
            ->assertOk()
            ->assertSee(__('dashboard.whatsapp_onboarding_result_coexistence_completed'))
            ->assertSee(__('dashboard.whatsapp_onboarding_next_step_coexistence'))
            ->assertSee(__('dashboard.whatsapp_onboarding_coexistence_reconnect_note'));
    }

    public function test_connect_page_shows_coexistence_available_when_configured(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'co-ui@wa-onboard.test',
                'password' => 'password',
            ]);

            $this->actingAs($user, 'tenant');
            Filament::setCurrentPanel(Filament::getPanel('tenant'));

            Livewire::test(ConnectWhatsAppPage::class)
                ->assertSuccessful()
                ->assertSee(__('dashboard.whatsapp_connect_coexistence_cta'))
                ->assertDontSee(__('dashboard.whatsapp_connect_config_required'));
        });
    }

    protected function fakeSuccessfulGraph(string $phoneId, string $wabaId): void
    {
        Http::fake(function (Request $request) use ($phoneId, $wabaId) {
            if (str_contains($request->url(), 'oauth/access_token')) {
                return Http::response(['access_token' => 'coexist-token', 'token_type' => 'bearer'], 200);
            }

            if (str_contains($request->url(), 'subscribed_apps')) {
                return Http::response(['success' => true], 200);
            }

            if (str_contains($request->url(), 'phone_numbers')) {
                return Http::response([
                    'data' => [[
                        'id' => $phoneId,
                        'display_phone_number' => '+201555000111',
                        'verified_name' => 'Coexist Biz',
                    ]],
                ], 200);
            }

            if (str_contains($request->url(), $phoneId) || str_contains($request->url(), $wabaId)) {
                return Http::response([
                    'id' => $phoneId,
                    'display_phone_number' => '+201555000111',
                    'verified_name' => 'Coexist Biz',
                ], 200);
            }

            return Http::response(['error' => ['message' => 'Unexpected']], 500);
        });
    }

    protected function issueState(
        string $tenantId,
        WhatsAppConnectionMethod $method = WhatsAppConnectionMethod::EmbeddedSignupCoexistence,
    ): string {
        return app(WhatsAppOnboardingStateService::class)->issue(
            tenantId: $tenantId,
            connectionMethod: $method,
            returnUrl: 'http://localhost/app/whatsapp-numbers',
            userId: 11,
        );
    }
}
