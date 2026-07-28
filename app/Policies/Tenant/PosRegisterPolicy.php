<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\PosRegister;
use App\Models\TenantUser;

class PosRegisterPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return true;
    }

    public function view(TenantUser $user, PosRegister $posRegister): bool
    {
        return true;
    }

    public function create(TenantUser $user): bool
    {
        return true;
    }

    public function update(TenantUser $user, PosRegister $posRegister): bool
    {
        return true;
    }

    public function delete(TenantUser $user, PosRegister $posRegister): bool
    {
        return true;
    }
}
