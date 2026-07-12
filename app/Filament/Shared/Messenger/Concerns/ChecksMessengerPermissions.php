<?php

namespace App\Filament\Shared\Messenger\Concerns;

use Illuminate\Support\Facades\Auth;

trait ChecksMessengerPermissions
{
    protected static function canMessengerPermission(string $tenantPermission, ?string $adminPermission = null): bool
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
