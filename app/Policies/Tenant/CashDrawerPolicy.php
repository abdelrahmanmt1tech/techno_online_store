<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\CashDrawer;
use App\Models\TenantUser;

class CashDrawerPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return true;
    }

    public function view(TenantUser $user, CashDrawer $cashDrawer): bool
    {
        return true;
    }

    public function create(TenantUser $user): bool
    {
        return true;
    }

    public function update(TenantUser $user, CashDrawer $cashDrawer): bool
    {
        return true;
    }

    public function delete(TenantUser $user, CashDrawer $cashDrawer): bool
    {
        return true;
    }
}
