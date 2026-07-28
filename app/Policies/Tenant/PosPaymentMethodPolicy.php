<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\PosPaymentMethod;
use App\Models\TenantUser;

class PosPaymentMethodPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return true;
    }

    public function view(TenantUser $user, PosPaymentMethod $posPaymentMethod): bool
    {
        return true;
    }

    public function create(TenantUser $user): bool
    {
        return true;
    }

    public function update(TenantUser $user, PosPaymentMethod $posPaymentMethod): bool
    {
        return true;
    }

    public function delete(TenantUser $user, PosPaymentMethod $posPaymentMethod): bool
    {
        return true;
    }
}
