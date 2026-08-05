<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Support\Modules\TenantModule;
use App\Support\Modules\TenantModuleGate;

/**
 * Optional module gate for Tenant Filament resources/pages that are not CRM-based.
 *
 * Override {@see requiredTenantModules()} to require one or more sellable modules
 * (any match grants access). Empty list = no module restriction.
 */
trait RequiresTenantModule
{
    /**
     * @return list<TenantModule|string>
     */
    protected static function requiredTenantModules(): array
    {
        return [];
    }

    public static function tenantModulesAllowAccess(): bool
    {
        $modules = static::requiredTenantModules();

        if ($modules === []) {
            return true;
        }

        return TenantModuleGate::anyEnabled(...$modules);
    }

    public static function canAccess(): bool
    {
        return static::tenantModulesAllowAccess() && parent::canAccess();
    }

    public static function canViewAny(): bool
    {
        return static::tenantModulesAllowAccess() && parent::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! static::tenantModulesAllowAccess()) {
            return false;
        }

        return parent::shouldRegisterNavigation();
    }
}
