<?php

namespace App\Providers;

use App\Http\Responses\Filament\PanelLoginResponse;
use App\Models\Admin;
use App\Models\Tenant\WhatsAppNumber;
use App\Observers\Tenant\WhatsAppNumberObserver;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
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
            if ($user->id === 1) {
                return true;
            }
            return null;
        });

        LanguageSwitch::configureUsing(function (LanguageSwitch $switcher) {
            $switcher
                ->locales(['ar', 'en']);
        });

        WhatsAppNumber::observe(WhatsAppNumberObserver::class);
    }
}
