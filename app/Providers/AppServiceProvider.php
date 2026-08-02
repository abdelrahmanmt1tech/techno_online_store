<?php

namespace App\Providers;

use App\Http\Responses\Filament\PanelLoginResponse;
use App\Models\Tenant\Client;
use App\Models\Tenant\Customer;
use App\Models\Tenant\MessengerPage;
use App\Models\Tenant\WhatsAppNumber;
use App\Observers\Tenant\MessengerPageObserver;
use App\Observers\Tenant\WhatsAppNumberObserver;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, PanelLoginResponse::class);
    }

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if (config('app.bypass_permissions')) {
                return $user !== null ? true : null;
            }

            if ($user?->id === 1) {
                return true;
            }

            return null;
        });

        LanguageSwitch::configureUsing(function (LanguageSwitch $switcher) {
            $switcher
                ->locales(['ar', 'en']);
        });

        // Soft morph aliases for CRM Client/Customer only.
        // Do NOT use enforceMorphMap here — the tenant app already stores many
        // FQCN morph types (TenantUser, Product, Seo, Media, permissions, etc.).
        Relation::morphMap([
            'client' => Client::class,
            'customer' => Customer::class,
        ]);

        WhatsAppNumber::observe(WhatsAppNumberObserver::class);
        MessengerPage::observe(MessengerPageObserver::class);
    }
}
