<?php

namespace Tests\Feature\WhatsApp;

use App\Filament\Tenant\Pages\ConnectWhatsAppPage;
use App\Filament\Tenant\Resources\WhatsAppNumbers\WhatsAppNumberResource;
use App\Models\TenantUser;
use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Onboarding\WhatsAppOnboardingStateService;
use Filament\Facades\Filament;
use Livewire\Livewire;

class WhatsAppOnboardingPhaseBTest extends WhatsAppTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.embedded_signup.config_id' => '1760158035346145',
            'whatsapp.embedded_signup.central_domain' => 'localhost',
            'whatsapp.embedded_signup.enforce_central_domain' => true,
            'whatsapp.meta_app_id' => 'meta-app-test',
            'app.url' => 'http://localhost',
            'app.bypass_permissions' => true,
        ]);
    }

    public function test_connect_whatsapp_page_renders_method_choices(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'agent@wa-onboard.test',
                'password' => 'password',
            ]);

            $this->actingAs($user, 'tenant');
            Filament::setCurrentPanel(Filament::getPanel('tenant'));

            Livewire::test(ConnectWhatsAppPage::class)
                ->assertSuccessful()
                ->assertSee(__('dashboard.whatsapp_connect_manual_title'))
                ->assertSee(__('dashboard.whatsapp_connect_api_only_title'))
                ->assertSee(__('dashboard.whatsapp_connect_coexistence_title'))
                ->assertSee(__('dashboard.whatsapp_connect_coming_soon'));
        });
    }

    public function test_manual_option_redirects_to_existing_create_number_page(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'manual@wa-onboard.test',
                'password' => 'password',
            ]);

            $this->actingAs($user, 'tenant');
            Filament::setCurrentPanel(Filament::getPanel('tenant'));

            Livewire::test(ConnectWhatsAppPage::class)
                ->call('chooseManual')
                ->assertRedirect(WhatsAppNumberResource::getUrl('create'));
        });
    }

    public function test_api_only_option_redirects_to_central_onboarding_start_with_signed_state(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () use ($tenant) {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'api@wa-onboard.test',
                'password' => 'password',
            ]);

            $this->actingAs($user, 'tenant');
            Filament::setCurrentPanel(Filament::getPanel('tenant'));

            $component = Livewire::test(ConnectWhatsAppPage::class)
                ->call('chooseApiOnly');

            $redirect = $component->effects['redirect'] ?? null;
            $this->assertIsString($redirect);
            $this->assertStringContainsString('http://localhost/whatsapp/onboarding/start?state=', $redirect);

            $query = parse_url($redirect, PHP_URL_QUERY);
            parse_str((string) $query, $params);
            $this->assertArrayHasKey('state', $params);

            $state = app(WhatsAppOnboardingStateService::class)->parse($params['state']);
            $this->assertSame((string) $tenant->getTenantKey(), $state->tenantId);
            $this->assertSame(WhatsAppConnectionMethod::EmbeddedSignupApiOnly, $state->connectionMethod);
        });
    }

    public function test_coexistence_option_is_gated_and_does_not_redirect(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'coexist@wa-onboard.test',
                'password' => 'password',
            ]);

            $this->actingAs($user, 'tenant');
            Filament::setCurrentPanel(Filament::getPanel('tenant'));

            $component = Livewire::test(ConnectWhatsAppPage::class)
                ->call('chooseCoexistence')
                ->assertNotified(__('dashboard.whatsapp_onboarding_coexistence_coming_soon'));

            $this->assertArrayNotHasKey('redirect', $component->effects);
        });
    }

    public function test_central_start_accepts_valid_state_and_rejects_invalid(): void
    {
        $service = app(WhatsAppOnboardingStateService::class);

        $token = $service->issue(
            tenantId: 'tenant-central',
            connectionMethod: WhatsAppConnectionMethod::EmbeddedSignupApiOnly,
            returnUrl: 'http://localhost/app/whatsapp-numbers',
        );

        $this->get('/whatsapp/onboarding/start?state='.urlencode($token))
            ->assertOk()
            ->assertSee('tenant-central')
            ->assertSee('1760158035346145');

        $this->get('/whatsapp/onboarding/start?state=not-valid')
            ->assertForbidden();
    }

    public function test_central_callback_rejects_raw_tenant_id_without_signed_state(): void
    {
        $this->get('/whatsapp/onboarding/callback?tenant_id=forged-tenant')
            ->assertForbidden();
    }

    public function test_central_callback_and_status_accept_signed_state_only(): void
    {
        $service = app(WhatsAppOnboardingStateService::class);

        $token = $service->issue(
            tenantId: 'tenant-callback',
            connectionMethod: WhatsAppConnectionMethod::EmbeddedSignupApiOnly,
            returnUrl: 'http://localhost/app/whatsapp-numbers',
        );

        $this->get('/whatsapp/onboarding/callback?state='.urlencode($token).'&tenant_id=forged')
            ->assertOk()
            ->assertSee('tenant-callback')
            ->assertDontSee('forged-should-not-matter');

        $this->get('/whatsapp/onboarding/status?state='.urlencode($token))
            ->assertOk()
            ->assertSee('tenant-callback');
    }

    public function test_wrong_host_is_rejected_when_central_domain_enforced(): void
    {
        config([
            'whatsapp.embedded_signup.central_domain' => 'online-store.technomasrsystems.com',
            'whatsapp.embedded_signup.enforce_central_domain' => true,
        ]);

        $token = app(WhatsAppOnboardingStateService::class)->issue(
            tenantId: 'tenant-host',
            connectionMethod: WhatsAppConnectionMethod::EmbeddedSignupApiOnly,
            returnUrl: 'http://localhost/app/whatsapp-numbers',
        );

        $this->get('/whatsapp/onboarding/start?state='.urlencode($token))
            ->assertForbidden();
    }
}
