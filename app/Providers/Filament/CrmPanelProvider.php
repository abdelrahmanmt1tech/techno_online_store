<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Crm\Widgets\CrmStatsOverview;
use App\Filament\Tenant\Resources\Clients\ClientResource;
use App\Filament\Tenant\Resources\LeadSources\LeadSourceResource;
use App\Filament\Tenant\Resources\Suppliers\SupplierResource;
use App\Http\Middleware\EnsureTenantIsInitialized;
use App\Http\Middleware\TenantAuthenticateSession;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class CrmPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('crm')
            ->path('app/crm')
            ->authGuard('tenant')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Teal,
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
            ])
            ->discoverResources(in: app_path('Filament/Crm/Resources'), for: 'App\\Filament\\Crm\\Resources')
            ->discoverPages(in: app_path('Filament/Crm/Pages'), for: 'App\\Filament\\Crm\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->resources([
                ClientResource::class,
                LeadSourceResource::class,
                SupplierResource::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Crm/Widgets'), for: 'App\\Filament\\Crm\\Widgets')
            ->widgets([
                CrmStatsOverview::class,
                AccountWidget::class,
            ])
            ->assets([
                Css::make('crm-custom-stylesheet', resource_path('css/crm-custom.css')),
            ])
            ->navigationGroups([
                NavigationGroup::make()->label(fn (): string => __('crm.nav.pipeline')),
                NavigationGroup::make()->label(fn (): string => __('crm.nav.settings')),
                NavigationGroup::make()->label(fn (): string => __('crm.nav.commissions')),
                NavigationGroup::make()->label(fn (): string => __('crm.nav.reports')),
            ])
            ->persistentMiddleware([
                InitializeTenancyByDomain::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                TenantAuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
                EnsureTenantIsInitialized::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
