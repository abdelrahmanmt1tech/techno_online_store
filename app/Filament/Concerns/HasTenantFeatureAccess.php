<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Support\Modules\TenantModule;

/**
 * CRM feature gate — resources/pages/widgets that use this trait require an active CRM package.
 */
trait HasTenantFeatureAccess
{
    public static function passesTenantFeatureGate(): bool
    {
        return tenant_module_enabled(TenantModule::Crm);
    }
}
