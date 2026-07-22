<?php

namespace App\Models\Tenant\Concerns;

trait BelongsToTenantConnection
{
    public function getConnectionName(): ?string
    {
        return 'tenant';
    }
}
