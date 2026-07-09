<?php

namespace App\Support;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Http\Request;

class FilamentPanelResolver
{
    public static function forRequest(?Request $request = null): Panel
    {
        $request ??= request();

        $panelId = session('filament.login_panel_id');

        if (filled($panelId)) {
            $rememberedPanel = Filament::getPanel($panelId, isStrict: false);

            if ($rememberedPanel !== null) {
                return $rememberedPanel;
            }
        }

        $referer = (string) $request->headers->get('Referer', '');

        if (str_contains($referer, '/app')) {
            return Filament::getPanel('tenant');
        }

        if (str_contains($referer, '/admin')) {
            return Filament::getPanel('admin');
        }

        if ($request->is('app', 'app/*')) {
            return Filament::getPanel('tenant');
        }

        if ($request->is('admin', 'admin/*')) {
            return Filament::getPanel('admin');
        }

        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', []);

        if (! in_array($host, $centralDomains, true)) {
            return Filament::getPanel('tenant');
        }

        return Filament::getCurrentPanel() ?? Filament::getDefaultPanel();
    }

    public static function rememberPanel(Panel $panel): void
    {
        session()->put('filament.login_panel_id', $panel->getId());
    }

    public static function forgetPanel(): void
    {
        session()->forget('filament.login_panel_id');
    }
}
