<?php

namespace App\Messenger\Actions;

use App\Messenger\Enums\MessengerPageStatus;
use App\Messenger\Services\MessengerTenantContextService;
use App\Models\MessengerPageRegistry;
use App\Models\Tenant;
use App\Models\Tenant\MessengerPage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SyncMessengerPageStatusAction
{
    public function __construct(
        protected MessengerTenantContextService $tenantContext,
        protected SyncMessengerPageRegistryAction $syncRegistry,
    ) {}

    public function execute(
        MessengerPageRegistry $registry,
        bool $isActive,
        ?MessengerPageStatus $status = null,
    ): MessengerPageRegistry {
        $tenant = Tenant::query()->findOrFail($registry->tenant_id);

        return DB::connection(config('tenancy.database.central_connection', config('database.default')))
            ->transaction(function () use ($tenant, $registry, $isActive, $status) {
                $updatedRegistry = null;

                $this->tenantContext->runForTenant($tenant, function () use ($registry, $isActive, $status, &$updatedRegistry) {
                    $page = MessengerPage::query()->findOrFail($registry->tenant_messenger_page_id);

                    $resolvedStatus = $status ?? ($isActive ? MessengerPageStatus::Active : MessengerPageStatus::Disabled);

                    $updates = [
                        'is_active' => $isActive,
                        'status' => $resolvedStatus,
                    ];

                    if (! $isActive) {
                        $updates['disconnected_at'] = now();
                    }

                    $page->update($updates);

                    $updatedRegistry = $this->syncRegistry->execute($page->fresh());
                });

                if ($updatedRegistry === null) {
                    throw new RuntimeException('Failed to sync Messenger page status to tenant database.');
                }

                return $updatedRegistry;
            });
    }
}
