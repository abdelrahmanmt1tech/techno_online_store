<?php

namespace App\Providers;

use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->id == 1 ?: null;
        });

        LanguageSwitch::configureUsing(function (LanguageSwitch $switcher) {
            $switcher
                ->locales(['ar', 'en']);
        });
    }
}
