<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\CashierSession;
use App\Models\TenantUser;

class CashierSessionPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return true;
    }

    public function view(TenantUser $user, CashierSession $cashierSession): bool
    {
        return true;
    }

    public function create(TenantUser $user): bool
    {
        return $user !== null;
    }

    public function update(TenantUser $user, CashierSession $cashierSession): bool
    {
        return false;
    }

    public function delete(TenantUser $user, CashierSession $cashierSession): bool
    {
        return false;
    }
}
