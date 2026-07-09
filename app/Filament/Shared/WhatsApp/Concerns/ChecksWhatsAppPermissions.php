<?php

namespace App\Filament\Shared\WhatsApp\Concerns;

use Illuminate\Support\Facades\Auth;

trait ChecksWhatsAppPermissions
{
    protected static function canWhatsAppPermission(string $tenantPermission, ?string $adminPermission = null): bool
    {
        if (config('app.bypass_permissions')) {
            return true;
        }

        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        $panelId = filament()->getCurrentPanel()?->getId();

        if ($panelId === 'admin') {
            return $user->can($adminPermission ?? $tenantPermission);
        }

        return $user->can($tenantPermission);
    }
}
