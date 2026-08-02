<?php

declare(strict_types=1);

namespace App\Filament\Crm;

use App\Filament\Concerns\HasTenantFeatureAccess;
use Filament\Pages\Page;

/**
 * Base for CRM pages — feature gate before permission canAccess.
 */
abstract class CrmPage extends Page
{
    use HasTenantFeatureAccess;

    public static function canAccess(): bool
    {
        return static::passesTenantFeatureGate() && static::canAccessByPermission();
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (static::$shouldRegisterNavigation === false) {
            return false;
        }

        return static::canAccess();
    }

    public static function canAccessByPermission(): bool
    {
        return true;
    }
}
