<?php

namespace App\Filament\Tenant\Pages;

use App\Models\TenantPackage;
use App\Models\TenantThemeSubscription;
use App\Support\Modules\TenantModule;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MySubscriptionsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::CreditCard;

    protected static ?string $slug = 'my-subscriptions';

    protected static ?int $navigationSort = 58;

    protected string $view = 'filament.tenant.pages.my-subscriptions';

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.subscriptions_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.my_subscriptions');
    }

    public function getTitle(): string|Htmlable
    {
        return __('dashboard.my_subscriptions');
    }

    public static function canAccess(): bool
    {
        return Auth::user()->can('subscriptions.view');
    }

    public function getPackages(): Collection
    {
        return TenantPackage::on('mysql')
            ->where('tenant_id', tenant()->getTenantKey())
            ->with(['package', 'currency'])
            ->orderByDesc('started_at')
            ->get();
    }

    public function getActivePackage(): ?TenantPackage
    {
        return TenantPackage::on('mysql')
            ->where('tenant_id', tenant()->getTenantKey())
            ->with(['package', 'currency'])
            ->active()
            ->orderByDesc('started_at')
            ->first();
    }

    public function getThemeSubscription(): ?TenantThemeSubscription
    {
        return TenantThemeSubscription::on('mysql')
            ->where('tenant_id', tenant()->getTenantKey())
            ->where('status', 'active')
            ->with('theme')
            ->orderByDesc('id')
            ->first();
    }

    public function getEnabledModules(): array
    {
        $modules = [];

        TenantPackage::on('mysql')
            ->where('tenant_id', tenant()->getTenantKey())
            ->with('package')
            ->active()
            ->get()
            ->each(function (TenantPackage $tenantPackage) use (&$modules): void {
                if ($tenantPackage->package) {
                    $modules = array_merge($modules, $tenantPackage->package->enabledModules());
                }
            });

        $modules = array_values(array_unique($modules));

        return array_map(
            fn (string $key): string => TenantModule::from($key)->label(),
            $modules,
        );
    }

    public function formatDuration(TenantPackage $tenantPackage): string
    {
        return $tenantPackage->duration.' '.__('dashboard.'.$tenantPackage->duration_type);
    }
}
