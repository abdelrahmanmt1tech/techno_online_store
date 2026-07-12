<?php

namespace App\Messenger\Actions;

use App\Models\MessengerPageRegistry;
use App\Models\Tenant\MessengerPage;

class SyncMessengerPageRegistryAction
{
    public function execute(MessengerPage $page): MessengerPageRegistry
    {
        $tenant = tenant();

        if ($tenant === null) {
            throw new \RuntimeException('Tenant context is required to sync Messenger page registry.');
        }

        return MessengerPageRegistry::query()->updateOrCreate(
            ['page_id' => $page->page_id],
            [
                'tenant_id' => $tenant->getTenantKey(),
                'tenant_messenger_page_id' => $page->getKey(),
                'page_name' => $page->page_name,
                'connection_method' => $page->connection_method,
                'token_source' => $page->token_source,
                'status' => $page->status,
                'webhook_status' => $page->webhook_status,
                'is_default' => (bool) ($page->is_default ?? false),
                'is_active' => (bool) ($page->is_active ?? true),
                'last_inbound_at' => $page->last_inbound_at,
                'last_outbound_at' => $page->last_outbound_at,
                'last_health_check_at' => $page->last_health_check_at,
            ],
        );
    }

    public function deleteFromRegistry(MessengerPage $page): void
    {
        MessengerPageRegistry::query()
            ->where('page_id', $page->page_id)
            ->delete();
    }
}
