<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Category;
use App\Models\TenantThemeSubscription;
use App\Models\Theme;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;

class BrowseThemesPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Swatch;

    protected static ?int $navigationSort = 60;

    protected string $view = 'filament.tenant.pages.browse-themes';

    public ?string $category = null;

    public string $priceFilter = 'all';

    #[Locked]
    public ?int $themeToSubscribe = null;

    public function getThemes()
    {
        $subscribedIds = $this->getSubscribedThemeIds();

        return Theme::on('mysql')
            ->with('categories')
            ->where('is_active', true)
            ->when($subscribedIds, fn ($q) => $q->whereNotIn('id', $subscribedIds))
            ->when($this->category, function ($q) {
                $themeIds = DB::connection('mysql')
                    ->table('category_theme')
                    ->whereIn('category_id', function ($q) {
                        $q->select('id')->from('categories')->where('slug', $this->category);
                    })
                    ->pluck('theme_id');

                $q->whereIn('id', $themeIds);
            })
            ->when($this->priceFilter === 'free', fn ($q) => $q->where('is_free', true))
            ->when($this->priceFilter === 'paid', fn ($q) => $q->where('is_free', false))
            ->orderBy('order')
            ->orderBy('name')
            ->get();
    }

    public function getCategories()
    {
        $activeThemeIds = Theme::on('mysql')->where('is_active', true)->pluck('id');

        $categoryIds = DB::connection('mysql')
            ->table('category_theme')
            ->whereIn('theme_id', $activeThemeIds)
            ->pluck('category_id')
            ->unique();

        return Category::on('mysql')
            ->whereIn('id', $categoryIds)
            ->orderBy('order')
            ->get();
    }

    public function getSubscribedThemeIds(): array
    {
        $latest = TenantThemeSubscription::on('mysql')
            ->where('tenant_id', tenant()->getTenantKey())
            ->where('status', 'active')
            ->orderBy('id', 'desc')
            ->first();

        return $latest ? [$latest->theme_id] : [];
    }

    public function confirmSubscribe(int $themeId): void
    {
        $this->themeToSubscribe = $themeId;
        $this->dispatch('open-modal', id: 'confirm-subscribe');
    }

    public function subscribe(): void
    {
        $themeId = $this->themeToSubscribe;

        if (! $themeId) {
            return;
        }

        $theme = Theme::on('mysql')->findOrFail($themeId);

        $tenantId = tenant()->getTenantKey();

        TenantThemeSubscription::on('mysql')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $existing = TenantThemeSubscription::on('mysql')
            ->where('tenant_id', $tenantId)
            ->where('theme_id', $themeId)
            ->first();

        if ($existing) {
            $existing->update([
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => null,
            ]);
        } else {
            TenantThemeSubscription::on('mysql')->create([
                'tenant_id' => $tenantId,
                'theme_id' => $themeId,
                'price' => $theme->price ?? 0,
                'currency' => 'USD',
                'status' => 'active',
                'starts_at' => now(),
            ]);
        }

        $this->themeToSubscribe = null;

        $this->dispatch('close-modal', id: 'confirm-subscribe');

        Notification::make()
            ->title(__('dashboard.theme_subscribed_success'))
            ->success()
            ->send();
    }

    public function getSubscribedTheme(): ?Theme
    {
        $subscription = TenantThemeSubscription::on('mysql')
            ->where('tenant_id', tenant()->getTenantKey())
            ->where('status', 'active')
            ->orderBy('id', 'desc')
            ->first();

        if (! $subscription) {
            return null;
        }

        return Theme::on('mysql')
            ->with('categories')
            ->find($subscription->theme_id);
    }

    public function clearFilters(): void
    {
        $this->category = null;
        $this->priceFilter = 'all';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.themes_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.browse_themes');
    }

    public function getTitle(): string|Htmlable
    {
        return __('dashboard.browse_themes');
    }

    public static function canAccess(): bool
    {
        return Auth::user()->can('themes.browse');
    }
}
