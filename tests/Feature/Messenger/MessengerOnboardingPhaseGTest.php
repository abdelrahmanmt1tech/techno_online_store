<?php

namespace Tests\Feature\Messenger;

use App\Filament\Tenant\Pages\ConnectMessengerPage;
use App\Filament\Tenant\Resources\MessengerPages\MessengerPageResource;
use App\Messenger\Enums\MessengerConnectionMethod;
use App\Messenger\Enums\MessengerPageStatus;
use App\Messenger\Enums\MessengerTokenSource;
use App\Messenger\Onboarding\ConnectSelectedMessengerPagesAction;
use App\Messenger\Onboarding\MessengerOnboardingStateService;
use App\Models\MessengerOnboardingSession;
use App\Models\MessengerPageRegistry;
use App\Models\Tenant\MessengerPage;
use App\Models\TenantUser;
use Filament\Facades\Filament;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

class MessengerOnboardingPhaseGTest extends MessengerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'messenger.meta_app_id' => 'meta-app-test',
            'messenger.app_secret' => 'meta-app-secret',
            'messenger.graph_api_version' => 'v21.0',
            'messenger.facebook_login.config_id' => 'messenger-config-123',
            'messenger.facebook_login.redirect_uri' => 'http://localhost/messenger/onboarding/callback',
            'messenger.facebook_login.central_domain' => 'localhost',
            'messenger.facebook_login.enforce_central_domain' => true,
            'messenger.facebook_login.scopes' => 'pages_show_list,pages_manage_metadata,pages_messaging',
            'app.url' => 'http://localhost',
            'app.bypass_permissions' => true,
        ]);
    }

    public function test_start_requires_config_and_valid_signed_state(): void
    {
        $token = $this->issueState('tenant-start');

        $this->get('/messenger/onboarding/start?state='.urlencode($token))
            ->assertOk()
            ->assertSee(__('dashboard.messenger_connect_via_facebook'))
            ->assertSee(__('dashboard.messenger_onboarding_launch_cta'))
            ->assertSee('messenger-config-123');

        config(['messenger.facebook_login.config_id' => null]);

        $this->get('/messenger/onboarding/start?state='.urlencode($token))
            ->assertOk()
            ->assertSee(__('dashboard.messenger_onboarding_config_required_body'))
            ->assertDontSee(__('dashboard.messenger_onboarding_launch_cta'));

        $this->get('/messenger/onboarding/start?state=tampered')
            ->assertForbidden();
    }

    public function test_signed_state_is_issued_and_parsed(): void
    {
        $service = app(MessengerOnboardingStateService::class);
        $token = $service->issue(
            tenantId: 'tenant-abc',
            returnUrl: 'http://tenant.localhost/app/messenger-pages',
            userId: 42,
        );

        $state = $service->parse($token);

        $this->assertSame('tenant-abc', $state->tenantId);
        $this->assertSame(42, $state->userId);
        $this->assertSame('http://tenant.localhost/app/messenger-pages', $state->returnUrl);
        $this->assertNotEmpty($state->nonce);
        $this->assertFalse($state->isExpired());
    }

    public function test_callback_rejects_raw_tenant_id_and_tampered_state(): void
    {
        $this->get('/messenger/onboarding/callback?tenant_id=forged-tenant')
            ->assertForbidden();

        $this->get('/messenger/onboarding/callback?state=tampered&code=abc')
            ->assertForbidden();

        $this->post('/messenger/onboarding/connect', [
            'tenant_id' => 'forged-tenant',
            'page_ids' => ['1'],
        ])->assertForbidden();
    }

    public function test_callback_exchanges_code_lists_pages_and_hides_tokens_in_ui(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, 'oauth/access_token')) {
                return Http::response([
                    'access_token' => 'user-token-secret',
                    'token_type' => 'bearer',
                ], 200);
            }

            if (str_contains($url, 'me/accounts')) {
                return Http::response([
                    'data' => [[
                        'id' => 'page-111',
                        'name' => 'Demo Store Page',
                        'access_token' => 'page-token-secret',
                    ]],
                ], 200);
            }

            return Http::response(['error' => ['message' => 'unexpected']], 500);
        });

        $token = $this->issueState('tenant-callback');

        $this->get('/messenger/onboarding/callback?state='.urlencode($token).'&code=auth-code-1')
            ->assertRedirect();

        $state = app(MessengerOnboardingStateService::class)->parse($token);
        $session = MessengerOnboardingSession::query()->where('nonce', $state->nonce)->first();

        $this->assertNotNull($session);
        $this->assertSame('awaiting_page_selection', $session->status);
        $this->assertNull($session->user_access_token);
        $this->assertSame('page-token-secret', $session->pages_payload[0]['page_access_token'] ?? null);

        $hidden = $session->toArray();
        $this->assertArrayNotHasKey('pages_payload', $hidden);
        $this->assertArrayNotHasKey('user_access_token', $hidden);

        $this->get('/messenger/onboarding/pages?state='.urlencode($token))
            ->assertOk()
            ->assertSee('Demo Store Page')
            ->assertSee('page-111')
            ->assertDontSee('page-token-secret')
            ->assertDontSee('user-token-secret');
    }

    public function test_connect_selected_pages_encrypts_token_syncs_registry_without_token_and_subscribes(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'subscribed_apps')) {
                return Http::response(['success' => true], 200);
            }

            return Http::response(['error' => ['message' => 'unexpected']], 500);
        });

        $tenant = $this->createTenantWithDatabase();
        $token = $this->issueState((string) $tenant->getTenantKey());
        $state = app(MessengerOnboardingStateService::class)->parse($token);

        MessengerOnboardingSession::query()->create([
            'nonce' => $state->nonce,
            'tenant_id' => $state->tenantId,
            'user_id' => null,
            'status' => 'awaiting_page_selection',
            'pages_payload' => [[
                'page_id' => 'page-222',
                'page_name' => 'Connected Page',
                'page_access_token' => 'page-token-222',
            ]],
            'return_url' => $state->returnUrl,
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->post('/messenger/onboarding/connect', [
            'state' => $token,
            'page_ids' => ['page-222'],
        ])->assertRedirect(route('messenger.onboarding.status', ['state' => $token]));

        $tenant->run(function () {
            $page = MessengerPage::query()->where('page_id', 'page-222')->first();
            $this->assertNotNull($page);
            $this->assertSame('Connected Page', $page->page_name);
            $this->assertSame('page-token-222', $page->page_access_token);
            $this->assertSame(MessengerTokenSource::FacebookLogin, $page->token_source);
            $this->assertSame(MessengerConnectionMethod::FacebookLogin, $page->connection_method);
            $this->assertSame(MessengerPageStatus::Active, $page->status);
            $this->assertSame('subscribed', $page->webhook_status);
            $this->assertTrue($page->is_active);
            $this->assertTrue($page->is_default);
            $this->assertArrayNotHasKey('page_access_token', $page->toArray());
        });

        $registry = MessengerPageRegistry::query()->where('page_id', 'page-222')->first();
        $this->assertNotNull($registry);
        $this->assertSame((string) $tenant->getTenantKey(), $registry->tenant_id);
        $this->assertFalse(isset($registry->page_access_token));
        $this->assertArrayNotHasKey('page_access_token', $registry->getAttributes());

        $session = MessengerOnboardingSession::query()->where('nonce', $state->nonce)->first();
        $this->assertSame('completed', $session->status);
        $this->assertNull($session->pages_payload);
        $this->assertSame(['page-222'], $session->connected_page_ids);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/page-222/subscribed_apps'));
    }

    public function test_subscribed_apps_failure_stores_safe_error_and_does_not_mark_subscribed(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Subscription denied', 'code' => 200],
            ], 400),
        ]);

        $tenant = $this->createTenantWithDatabase();
        $token = $this->issueState((string) $tenant->getTenantKey());
        $state = app(MessengerOnboardingStateService::class)->parse($token);

        MessengerOnboardingSession::query()->create([
            'nonce' => $state->nonce,
            'tenant_id' => $state->tenantId,
            'status' => 'awaiting_page_selection',
            'pages_payload' => [[
                'page_id' => 'page-333',
                'page_name' => 'Fail Page',
                'page_access_token' => 'page-token-333',
            ]],
            'return_url' => $state->returnUrl,
            'expires_at' => now()->addMinutes(15),
        ]);

        app(ConnectSelectedMessengerPagesAction::class)->execute($state, ['page-333']);

        $tenant->run(function () {
            $page = MessengerPage::query()->where('page_id', 'page-333')->first();
            $this->assertNotNull($page);
            $this->assertSame('failed', $page->webhook_status);
            $this->assertSame(MessengerPageStatus::ReconnectRequired, $page->status);
            $this->assertNotNull($page->last_error_message);
            $this->assertStringContainsString('Subscription denied', $page->last_error_message);
        });

        $session = MessengerOnboardingSession::query()->where('nonce', $state->nonce)->first();
        $this->assertSame('failed', $session->status);
    }

    public function test_duplicate_page_id_updates_existing_tenant_page(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['success' => true], 200),
        ]);

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            MessengerPage::query()->create([
                'page_id' => 'page-dup',
                'page_name' => 'Old Name',
                'page_access_token' => 'old-token',
                'token_source' => MessengerTokenSource::Manual,
                'connection_method' => MessengerConnectionMethod::Manual,
                'status' => MessengerPageStatus::Active,
                'webhook_status' => 'pending',
                'is_active' => true,
                'is_default' => true,
            ]);
        });

        $token = $this->issueState((string) $tenant->getTenantKey());
        $state = app(MessengerOnboardingStateService::class)->parse($token);

        MessengerOnboardingSession::query()->create([
            'nonce' => $state->nonce,
            'tenant_id' => $state->tenantId,
            'status' => 'awaiting_page_selection',
            'pages_payload' => [[
                'page_id' => 'page-dup',
                'page_name' => 'New Name',
                'page_access_token' => 'new-token',
            ]],
            'return_url' => $state->returnUrl,
            'expires_at' => now()->addMinutes(15),
        ]);

        app(ConnectSelectedMessengerPagesAction::class)->execute($state, ['page-dup']);

        $tenant->run(function () {
            $this->assertSame(1, MessengerPage::query()->where('page_id', 'page-dup')->count());
            $page = MessengerPage::query()->where('page_id', 'page-dup')->first();
            $this->assertSame('New Name', $page->page_name);
            $this->assertSame('new-token', $page->page_access_token);
            $this->assertSame(MessengerConnectionMethod::FacebookLogin, $page->connection_method);
            $this->assertSame('subscribed', $page->webhook_status);
        });
    }

    public function test_manual_messenger_connection_still_works(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $page = MessengerPage::query()->create([
                'page_id' => 'page-manual',
                'page_name' => 'Manual Page',
                'page_access_token' => 'manual-token',
                'token_source' => MessengerTokenSource::Manual,
                'connection_method' => MessengerConnectionMethod::Manual,
                'status' => MessengerPageStatus::Active,
                'webhook_status' => 'pending',
                'is_active' => true,
                'is_default' => true,
            ]);

            $this->assertSame(MessengerConnectionMethod::Manual, $page->fresh()->connection_method);
            $this->assertSame('manual-token', $page->fresh()->page_access_token);
        });

        $this->assertNotNull(
            MessengerPageRegistry::query()->where('page_id', 'page-manual')->first()
        );
    }

    public function test_connect_messenger_page_renders_choices_and_gates_facebook_login(): void
    {
        config(['messenger.facebook_login.config_id' => null]);

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'agent@messenger-onboard.test',
                'password' => 'password',
            ]);

            $this->actingAs($user, 'tenant');
            Filament::setCurrentPanel(Filament::getPanel('tenant'));

            Livewire::test(ConnectMessengerPage::class)
                ->assertSuccessful()
                ->assertSee(__('dashboard.messenger_connect_manual_title'))
                ->assertSee(__('dashboard.messenger_connect_facebook_title'))
                ->assertSee(__('dashboard.messenger_connect_config_required'))
                ->call('chooseFacebookLogin')
                ->assertNotified(__('dashboard.messenger_onboarding_config_required_title'));
        });
    }

    public function test_facebook_login_option_redirects_with_signed_state(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () use ($tenant) {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'fb@messenger-onboard.test',
                'password' => 'password',
            ]);

            $this->actingAs($user, 'tenant');
            Filament::setCurrentPanel(Filament::getPanel('tenant'));

            $component = Livewire::test(ConnectMessengerPage::class)
                ->call('chooseFacebookLogin');

            $redirect = $component->effects['redirect'] ?? null;
            $this->assertIsString($redirect);
            $this->assertStringContainsString('http://localhost/messenger/onboarding/start?state=', $redirect);

            $query = parse_url($redirect, PHP_URL_QUERY);
            parse_str((string) $query, $params);
            $state = app(MessengerOnboardingStateService::class)->parse($params['state']);
            $this->assertSame((string) $tenant->getTenantKey(), $state->tenantId);
        });
    }

    public function test_manual_option_redirects_to_create_page(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'manual@messenger-onboard.test',
                'password' => 'password',
            ]);

            $this->actingAs($user, 'tenant');
            Filament::setCurrentPanel(Filament::getPanel('tenant'));

            Livewire::test(ConnectMessengerPage::class)
                ->call('chooseManual')
                ->assertRedirect(MessengerPageResource::getUrl('create'));
        });
    }

    protected function issueState(string $tenantId): string
    {
        return app(MessengerOnboardingStateService::class)->issue(
            tenantId: $tenantId,
            returnUrl: 'http://tenant.localhost/app/messenger-pages',
            userId: 7,
        );
    }
}
