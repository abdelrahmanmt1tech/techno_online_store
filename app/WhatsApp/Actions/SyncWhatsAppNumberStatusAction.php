<?php

namespace App\WhatsApp\Actions;

use App\Models\Tenant;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\WhatsAppNumberRegistry;
use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use App\WhatsApp\Services\WhatsAppTenantContextService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SyncWhatsAppNumberStatusAction
{
    public function __construct(
        protected WhatsAppTenantContextService $tenantContext,
        protected SyncWhatsAppNumberRegistryAction $syncRegistry,
    ) {}

    public function execute(WhatsAppNumberRegistry $registry, bool $isActive, ?WhatsAppConnectionStatus $status = null): WhatsAppNumberRegistry
    {
        $tenant = Tenant::query()->findOrFail($registry->tenant_id);

        return DB::connection(config('tenancy.database.central_connection', config('database.default')))
            ->transaction(function () use ($tenant, $registry, $isActive, $status) {
                $updatedRegistry = null;

                $this->tenantContext->runForTenant($tenant, function () use ($registry, $isActive, $status, &$updatedRegistry) {
                    $number = WhatsAppNumber::query()->findOrFail($registry->tenant_whatsapp_number_id);

                    $number->update([
                        'is_active' => $isActive,
                        'status' => $status ?? ($isActive ? WhatsAppConnectionStatus::Active : WhatsAppConnectionStatus::Disabled),
                    ]);

                    $updatedRegistry = $this->syncRegistry->execute($number->fresh());
                });

                if ($updatedRegistry === null) {
                    throw new RuntimeException('Failed to sync WhatsApp number status to tenant database.');
                }

                return $updatedRegistry;
            });
    }
}
