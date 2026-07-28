<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Brand;
use App\Models\TenantUser;

class BrandPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return true;
    }

    public function view(TenantUser $user, Brand $brand): bool
    {
        return true;
    }

    public function create(TenantUser $user): bool
    {
        return true;
    }

    public function update(TenantUser $user, Brand $brand): bool
    {
        return true;
    }

    public function delete(TenantUser $user, Brand $brand): bool
    {
        return true;
    }
}
