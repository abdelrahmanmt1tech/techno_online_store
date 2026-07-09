<?php

namespace App\Http\Responses\Filament;

use App\Support\FilamentPanelResolver;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class PanelLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $panel = FilamentPanelResolver::forRequest($request);
        Filament::setCurrentPanel($panel);
        FilamentPanelResolver::forgetPanel();

        return redirect()->to($panel->getUrl());
    }
}
