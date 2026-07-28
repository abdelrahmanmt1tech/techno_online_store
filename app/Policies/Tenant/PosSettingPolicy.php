<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\PosSetting;
use App\Models\TenantUser;

class PosSettingPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return true;
    }

    public function view(TenantUser $user, PosSetting $posSetting): bool
    {
        return true;
    }

    public function create(TenantUser $user): bool
    {
        return false;
    }

    public function update(TenantUser $user, PosSetting $posSetting): bool
    {
        return true;
    }

    public function delete(TenantUser $user, PosSetting $posSetting): bool
    {
        return false;
    }
}
