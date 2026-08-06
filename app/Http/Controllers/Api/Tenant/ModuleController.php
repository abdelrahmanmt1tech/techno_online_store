<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Central\PageListResource;
use App\Models\Page;
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

        $pages = Page::on($this->centralConnection())
            ->whereIn('slug', Page::PROTECTED_SLUGS)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse([
            'key' => TenantModule::Store->value,
            'label' => TenantModule::Store->label(),
            'enabled' => $enabled,
            'available' => $enabled,
            'subscribed' => $enabled,
            'pages' => PageListResource::collection($pages),
        ], __('messages.fetched_successfully'));
    }

    private function centralConnection(): string
    {
        return config('tenancy.database.central_connection', config('database.default'));
    }
}
