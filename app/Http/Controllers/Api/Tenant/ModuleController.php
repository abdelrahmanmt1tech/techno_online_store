<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Support\Modules\TenantModule;
use App\Support\Modules\TenantModuleGate;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Public tenant module status for storefront / SPA bootstrap.
 *
 * Not gated by EnsureTenantModuleActive — the client must be able to read
 * whether the store module is available even when it is off.
 */
class ModuleController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/tenant/modules
     */
    public function index(): JsonResponse
    {
        $enabledKeys = TenantModuleGate::enabledKeys();
        $storeEnabled = TenantModuleGate::storeEnabled();

        $modules = [];

        foreach (TenantModule::cases() as $module) {
            $modules[$module->value] = [
                'key' => $module->value,
                'label' => $module->label(),
                'enabled' => in_array($module->value, $enabledKeys, true),
            ];
        }

        return $this->successResponse([
            'store' => [
                'key' => TenantModule::Store->value,
                'label' => TenantModule::Store->label(),
                'enabled' => $storeEnabled,
                'available' => $storeEnabled,
                'subscribed' => $storeEnabled,
            ],
            'modules' => $modules,
            'enabled_modules' => $enabledKeys,
        ], __('messages.fetched_successfully'));
    }

    /**
     * GET /api/tenant/modules/store
     */
    public function store(): JsonResponse
    {
        $enabled = TenantModuleGate::storeEnabled();

        return $this->successResponse([
            'key' => TenantModule::Store->value,
            'label' => TenantModule::Store->label(),
            'enabled' => $enabled,
            'available' => $enabled,
            'subscribed' => $enabled,
            'terms_link' => route('central.pages.show', ['slug' => 'terms-and-conditions']),
            'policy_link' => route('central.pages.show', ['slug' => 'privacy-policy']),
            'dashboard_link' => url('/app'),
        ], __('messages.fetched_successfully'));
    }
}
