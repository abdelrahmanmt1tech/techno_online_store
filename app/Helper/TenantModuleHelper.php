<?php

use App\Support\Modules\TenantModule;
use App\Support\Modules\TenantModuleGate;

if (! function_exists('tenant_module_enabled')) {
    /**
     * Shared check: is this sellable module available for the current merchant?
     *
     * Resolves against active tenant packages via {@see TenantModuleGate}.
     * Development bypass: config('app.bypass_permissions') opens every module.
     *
     * @param  TenantModule|string  $module  store|pos|crm|accounting
     */
    function tenant_module_enabled(TenantModule|string $module): bool
    {
        return TenantModuleGate::enabled($module);
    }
}

if (! function_exists('tenant_module_any_enabled')) {
    /**
     * True when any of the given modules is enabled (e.g. shared catalog: store|pos).
     *
     * @param  TenantModule|string  ...$modules
     */
    function tenant_module_any_enabled(TenantModule|string ...$modules): bool
    {
        return TenantModuleGate::anyEnabled(...$modules);
    }
}

if (! function_exists('tenant_accounting_active')) {
    /**
     * True when the Accounting module is available — gate for automatic journal posting.
     */
    function tenant_accounting_active(): bool
    {
        return TenantModuleGate::accountingActive();
    }
}
