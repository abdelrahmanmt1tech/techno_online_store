<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TenantUserCredential;
use Database\Seeders\TenantDataSeeder;

class SeedTenantDatabase
{
    public function __construct(
        protected Tenant $tenant,
        protected ?string $password = null,
    ) {}

    public function handle(): void
    {
        $email = null;

        $this->tenant->run(function () use (&$email) {
            $centralDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
            $subdomain = str_replace('.'.$centralDomain, '', $this->tenant->domains()->first()?->domain ?? '');

            $email = $this->tenant->email ?? 'admin@'.($subdomain ?: $this->tenant->id).'.'.$centralDomain;

            $userClass = config('auth.providers.tenant_users.model');

            $password = $this->password ?? 'password';

            $user = $userClass::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $this->tenant->name.' Admin',
                    'password' => $password,
                    'email_verified_at' => now(),
                    'is_admin' => true,
                ]
            );

            StoreTenantPermissionsArray();
            $role = setupStoreAdminRole();
            $user->assignRole($role);

            (new TenantDataSeeder)->run();
        });

        if ($email) {
            TenantUserCredential::updateOrCreate(
                ['email' => $email],
                ['tenant_id' => $this->tenant->id]
            );
        }
    }
}
