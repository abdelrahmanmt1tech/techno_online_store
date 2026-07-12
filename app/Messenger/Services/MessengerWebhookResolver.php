<?php

namespace App\Messenger\Services;

use App\Models\MessengerPageRegistry;
use App\Models\Tenant;

class MessengerWebhookResolver
{
    public function resolveByPageId(?string $pageId): ?MessengerPageRegistry
    {
        if (blank($pageId)) {
            return null;
        }

        return MessengerPageRegistry::query()
            ->where('page_id', $pageId)
            ->first();
    }

    public function resolveTenant(?string $pageId): ?Tenant
    {
        $registry = $this->resolveByPageId($pageId);

        if ($registry === null) {
            return null;
        }

        return Tenant::query()->find($registry->tenant_id);
    }
}
