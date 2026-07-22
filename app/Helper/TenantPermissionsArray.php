<?php

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function tenantPermissionsArray(): array
{
    return [
        [
            'name' => 'dashboard.permissions_groups.whatsapp',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.view_numbers'],
                ['name' => 'dashboard.permissions.update', 'key' => 'whatsapp.manage_numbers'],
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.view_inbox'],
                ['name' => 'dashboard.permissions.create', 'key' => 'whatsapp.send_messages'],
                ['name' => 'dashboard.permissions.update', 'key' => 'whatsapp.switch_reply_number'],
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.view_templates'],
                ['name' => 'dashboard.permissions.update', 'key' => 'whatsapp.manage_templates'],
                ['name' => 'dashboard.permissions.create', 'key' => 'whatsapp.send_template_messages'],
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.view_webhook_events'],
            ],
        ],
        [
            'name' => 'dashboard.permissions_groups.messenger',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'messenger.view_pages'],
                ['name' => 'dashboard.permissions.update', 'key' => 'messenger.manage_pages'],
                ['name' => 'dashboard.permissions.view', 'key' => 'messenger.view_inbox'],
                ['name' => 'dashboard.permissions.create', 'key' => 'messenger.send_messages'],
                ['name' => 'dashboard.permissions.view', 'key' => 'messenger.view_webhook_events'],
            ],
        ],
        [
            'name' => 'dashboard.permissions_groups.store',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'governorates.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'governorates.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'governorates.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'governorates.delete'],
                ['name' => 'dashboard.permissions.view', 'key' => 'orders.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'orders.update'],
                ['name' => 'dashboard.permissions.view', 'key' => 'coupons.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'coupons.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'coupons.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'coupons.delete'],
                ['name' => 'dashboard.permissions.view', 'key' => 'code-settings.view'],
                ['name' => 'dashboard.permissions.view', 'key' => 'footer-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'footer-settings.update'],
                ['name' => 'dashboard.permissions.view', 'key' => 'reviews.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'reviews.update'],
                ['name' => 'dashboard.permissions.view', 'key' => 'store-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'store-settings.update'],
            ],
        ],
    ];
}

function StoreTenantPermissionsArray(): void
{
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $permissionsArray = collect(tenantPermissionsArray());
    $newPermissions = collect();

    foreach ($permissionsArray as $group) {
        foreach ($group['permissions'] as $perm) {
            $newPermissions->push([
                'key' => $perm['key'],
                'name' => $perm['name'],
                'group' => $group['name'],
            ]);
        }
    }

    $guard = 'tenant';
    $newKeys = $newPermissions->pluck('key')->toArray();

    DB::transaction(function () use ($guard, $newKeys, $newPermissions): void {
        $existing = Permission::where('guard_name', $guard)->get();
        $existingKeys = $existing->pluck('name')->toArray();
        $toDelete = array_diff($existingKeys, $newKeys);

        if (! empty($toDelete)) {
            $permissionsToDelete = Permission::whereIn('name', $toDelete)
                ->where('guard_name', $guard)
                ->get(['id']);

            $ids = $permissionsToDelete->pluck('id')->filter()->values()->toArray();

            if (! empty($ids)) {
                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
                DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
                Permission::whereIn('id', $ids)->delete();
            }
        }

        foreach ($newPermissions as $perm) {
            Permission::updateOrCreate(
                ['name' => $perm['key'], 'guard_name' => $guard],
                [
                    'display_name' => $perm['name'],
                    'group_name' => $perm['group'],
                ]
            );
        }
    });

    app()[PermissionRegistrar::class]->forgetCachedPermissions();
}

function setupStoreAdminRole(): Role
{
    $role = Role::firstOrCreate([
        'name' => 'Store Admin',
        'guard_name' => 'tenant',
    ]);

    $permissions = collect(tenantPermissionsArray())
        ->flatMap(fn ($group) => collect($group['permissions'])->pluck('key'))
        ->values()
        ->toArray();

    $role->syncPermissions($permissions);

    return $role;
}
