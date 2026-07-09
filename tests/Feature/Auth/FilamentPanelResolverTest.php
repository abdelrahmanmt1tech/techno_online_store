<?php

namespace Tests\Feature\Auth;

use App\Support\FilamentPanelResolver;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Tests\TestCase;

class FilamentPanelResolverTest extends TestCase
{
    public function test_resolves_tenant_panel_from_app_referer(): void
    {
        $request = Request::create('/livewire/update', 'POST');
        $request->headers->set('Referer', 'http://demo.localhost:8000/app/login');

        $panel = FilamentPanelResolver::forRequest($request);

        $this->assertSame('tenant', $panel->getId());
    }

    public function test_resolves_tenant_panel_from_non_central_host(): void
    {
        $request = Request::create('/app/login', 'GET', server: ['HTTP_HOST' => 'demo.localhost']);

        $panel = FilamentPanelResolver::forRequest($request);

        $this->assertSame('tenant', $panel->getId());
    }

    public function test_resolves_admin_panel_from_admin_referer(): void
    {
        $request = Request::create('/livewire/update', 'POST');
        $request->headers->set('Referer', 'http://localhost:8000/admin/login');

        $panel = FilamentPanelResolver::forRequest($request);

        $this->assertSame('admin', $panel->getId());
    }

    public function test_remembered_panel_id_takes_priority(): void
    {
        session()->put('filament.login_panel_id', 'tenant');

        $request = Request::create('/livewire/update', 'POST');
        $request->headers->set('Referer', 'http://localhost:8000/admin/login');

        $panel = FilamentPanelResolver::forRequest($request);

        $this->assertSame('tenant', $panel->getId());
    }

    public function test_panel_login_response_targets_tenant_dashboard_url(): void
    {
        $request = Request::create('/livewire/update', 'POST', server: ['HTTP_HOST' => 'demo.localhost']);
        $request->headers->set('Referer', 'http://demo.localhost:8000/app/login');

        $panel = FilamentPanelResolver::forRequest($request);

        $this->assertStringContainsString('/app', $panel->getUrl());
        $this->assertSame('tenant', Filament::getPanel('tenant')->getId());
    }
}
