<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        StorePermissionsArray();

        $admin = Admin::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $role = Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'admin']
        );

        $permissions = collect(permissionsArray())
            ->flatMap(fn ($group) => collect($group['permissions'])->pluck('key'))
            ->values()
            ->toArray();

        $role->syncPermissions($permissions);

        $admin->assignRole('Super Admin');
    }
}
