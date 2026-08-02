<?php

namespace App\Support\Modules;

/**
 * Single reference point for “is this module available for the current tenant?”.
 *
 * IMPORTANT — stop here until billing is ready:
 * - Do not scatter subscription/plan checks across Filament resources or Actions.
 * - Always call {@see self::enabled()} / {@see tenant_module_enabled()}.
 * - Until real module subscriptions exist, every module returns true.
 *
 * Later: replace the body of {@see self::resolve()} only (tenant subscription rows,
 * central billing API, cache, etc.). Auto journal posting must also go through
 * {@see self::accountingActive()} so GL posts run only when Accounting is on.
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
     * REFERENCE HOOK — change only this method when module subscriptions go live.
     *
     * Current behaviour: always true (all modules open during development).
     */
    private static function resolve(TenantModule $module): bool
    {
        // TODO(modules-billing): look up active per-module subscription for tenant($module).
        // Until then every module is available.
        return true;
    }

    private static function normalize(TenantModule|string $module): TenantModule
    {
        if ($module instanceof TenantModule) {
            return $module;
        }

        return TenantModule::from($module);
    }
}
