<?php

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

function permissionsArray(): array
{
    return [

        // ── الأدوار ──
        [
            'name' => 'dashboard.permissions_groups.roles',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'roles-and-permission.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'roles-and-permission.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'roles-and-permission.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'roles-and-permission.destroy'],
            ],
        ],
        [
            'name' => 'dashboard.permissions_groups.tenants',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'tenants.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'tenants.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'tenants.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'tenants.delete'],
            ],
        ],
        [
            'name' => 'dashboard.permissions_groups.admins',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'admins.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'admins.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'admins.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'admins.delete'],
            ],
        ],

    ];
}

function StorePermissionsArray()
{
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $permissionsArray = collect(permissionsArray());

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

    $guard = 'admin';
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
