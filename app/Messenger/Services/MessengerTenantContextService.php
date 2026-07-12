<?php

namespace App\Messenger\Services;

use App\Models\Tenant;
use RuntimeException;

class MessengerTenantContextService
{
    public function initializeForTenant(Tenant $tenant): void
    {
        if (! $tenant->exists) {
            throw new RuntimeException('Invalid tenant.');
        }

        tenancy()->initialize($tenant);
    }

    public function runForTenant(Tenant $tenant, callable $callback): mixed
    {
        return $tenant->run($callback);
    }

    public function end(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
    }
}
