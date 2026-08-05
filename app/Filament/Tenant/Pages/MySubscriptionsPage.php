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
        return __('tenant_navigation.groups.settings_admin');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.my_subscriptions');
    }

    public function getTitle(): string|Htmlable
    {
        return __('dashboard.my_subscriptions');
    }

    public function getHeading(): string|Htmlable
    {
        return '';
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

    public function getActivePackages(): Collection
    {
        return TenantPackage::on('mysql')
            ->where('tenant_id', tenant()->getTenantKey())
            ->with(['package', 'currency'])
            ->active()
            ->orderByDesc('started_at')
            ->get();
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

    /**
     * @return list<string>
     */
    public function getEnabledModules(): array
    {
        $modules = [];

        foreach ($this->getActivePackages() as $tenantPackage) {
            if ($tenantPackage->package) {
                $modules = array_merge($modules, $tenantPackage->package->enabledModules());
            }
        }

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

    public function daysRemaining(?\Carbon\CarbonInterface $expiresAt): ?int
    {
        if ($expiresAt === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($expiresAt->copy()->startOfDay(), false);
    }

    public function progressPercent(TenantPackage $tenantPackage): int
    {
        if ($tenantPackage->started_at === null || $tenantPackage->expires_at === null) {
            return 100;
        }

        $total = max(1, $tenantPackage->started_at->diffInSeconds($tenantPackage->expires_at));
        $elapsed = max(0, $tenantPackage->started_at->diffInSeconds(now()));

        return (int) max(0, min(100, round((1 - ($elapsed / $total)) * 100)));
    }

    public function urgencyClass(?int $daysRemaining, string $status): string
    {
        if (! in_array($status, ['trial', 'active'], true)) {
            return 'is-inactive';
        }

        if ($daysRemaining === null) {
            return 'is-ok';
        }

        if ($daysRemaining < 0) {
            return 'is-expired';
        }

        if ($daysRemaining <= 7) {
            return 'is-critical';
        }

        if ($daysRemaining <= 30) {
            return 'is-warn';
        }

        return 'is-ok';
    }

    public function packageModulesLabel(TenantPackage $tenantPackage): string
    {
        $keys = $tenantPackage->package?->enabledModules() ?? [];

        if ($keys === []) {
            return '—';
        }

        return collect($keys)
            ->map(fn (string $key): string => TenantModule::tryFrom($key)?->label() ?? $key)
            ->implode(' · ');
    }
}
