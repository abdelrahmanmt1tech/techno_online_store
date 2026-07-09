<?php

namespace App\Filament\Auth;

use App\Support\FilamentPanelResolver;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Livewire\Attributes\Locked;

class Login extends \Filament\Auth\Pages\Login
{
    #[Locked]
    public string $filamentPanelId = '';

    public function mount(): void
    {
        $panel = Filament::getCurrentPanel() ?? FilamentPanelResolver::forRequest();
        Filament::setCurrentPanel($panel);
        $this->filamentPanelId = $panel->getId();
        FilamentPanelResolver::rememberPanel($panel);

        if (Filament::auth()->check()) {
            redirect()->to($panel->getUrl());
        }

        $this->form->fill();
    }

    public function hydrate(): void
    {
        if (blank($this->filamentPanelId)) {
            $panel = FilamentPanelResolver::forRequest();
            $this->filamentPanelId = $panel->getId();
        }

        Filament::setCurrentPanel(Filament::getPanel($this->filamentPanelId));
    }

    public function authenticate(): ?LoginResponse
    {
        $panel = Filament::getPanel($this->filamentPanelId);
        Filament::setCurrentPanel($panel);
        FilamentPanelResolver::rememberPanel($panel);

        return parent::authenticate();
    }
}
