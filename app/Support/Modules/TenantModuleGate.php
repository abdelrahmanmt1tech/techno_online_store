<?php

namespace App\Support\Modules;

use App\Models\Tenant;
use App\Models\TenantPackage;

/**
 * Single reference point for “is this module available for the current tenant?”.
 *
 * - Always call {@see self::enabled()} / {@see tenant_module_enabled()}.
 * - Gating is strict in production: a merchant with no active package gets no
 *   modules. During development the whole app is open via
 *   `config('app.bypass_permissions')` (true outside production).
 * - Modules come from the tenant's active `tenant_packages` rows: a full
 *   package grants every module, a partial package grants its single module.
 * - Auto journal posting must also go through {@see self::accountingActive()}
 *   so GL posts run only when Accounting is on.
 */
final class TenantModuleGate
{
    /**
     * Whether the merchant may use the given module.
     *
     * @param  TenantModule|string  $module  Enum or module key (store|pos|crm|accounting)
     */
    public static function enabled(TenantModule|string $module): bool
    {
        $module = self::normalize($module);

        return self::resolve($module);
    }

    /**
     * Accounting module is subscribed/active — required for automatic document→journal posting.
     */
    public static function accountingActive(): bool
    {
        return self::enabled(TenantModule::Accounting);
    }

    /**
     * Convenience aliases for callers.
     */
    public static function storeEnabled(): bool
    {
        return self::enabled(TenantModule::Store);
    }

    public static function posEnabled(): bool
    {
        return self::enabled(TenantModule::Pos);
    }

    public static function crmEnabled(): bool
    {
        return self::enabled(TenantModule::Crm);
    }

    /**
     * True when any of the given modules is enabled for the current tenant.
     *
     * @param  TenantModule|string  ...$modules
     */
    public static function anyEnabled(TenantModule|string ...$modules): bool
    {
        if ($modules === []) {
            return true;
        }

        foreach ($modules as $module) {
            if (self::enabled($module)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function enabledKeys(): array
    {
        $keys = [];

        foreach (TenantModule::cases() as $module) {
            if (self::enabled($module)) {
                $keys[] = $module->value;
            }
        }

        return $keys;
    }

    /**
     * Real gating against the current tenant's active packages.
     */
    private static function resolve(TenantModule $module): bool
    {
        if (config('app.bypass_permissions')) {
            return true;
        }

        $enabled = once(fn () => self::enabledModulesForCurrentTenant());

        return in_array($module->value, $enabled, true);
    }

    /**
     * Modules granted by the current tenant's active packages.
     *
     * @return list<string>
     */
    private static function enabledModulesForCurrentTenant(): array
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return [];
        }

        $modules = [];

        $tenant->packages()
            ->active()
            ->with('package')
            ->get()
            ->each(function (TenantPackage $tenantPackage) use (&$modules): void {
                if ($tenantPackage->package) {
                    $modules = array_merge($modules, $tenantPackage->package->enabledModules());
                }
            });

        return array_values(array_unique($modules));
    }

    private static function normalize(TenantModule|string $module): TenantModule
    {
        if ($module instanceof TenantModule) {
            return $module;
        }

        return TenantModule::from($module);
    }
}
