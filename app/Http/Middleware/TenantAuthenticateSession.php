<?php

namespace App\Http\Middleware;

use App\Support\FilamentPanelResolver;
use Filament\Facades\Filament;
use Filament\Http\Middleware\AuthenticateSession as Middleware;

class TenantAuthenticateSession extends Middleware
{
    protected function redirectTo($request): ?string
    {
        $panel = FilamentPanelResolver::forRequest($request);
        Filament::setCurrentPanel($panel);

        return Filament::getLoginUrl();
    }
}
