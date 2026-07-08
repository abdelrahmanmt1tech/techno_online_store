<?php

namespace App\Jobs;

use App\Models\Tenant;

class SeedTenantDatabase
{
    public function __construct(
        protected Tenant $tenant,
        protected ?string $password = null,
    ) {}

    public function handle(): void
    {
        $this->tenant->run(function () {
            $centralDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
            $subdomain = str_replace('.'.$centralDomain, '', $this->tenant->domains()->first()?->domain ?? '');

            $email = $this->tenant->email ?? 'admin@'.($subdomain ?: $this->tenant->id).'.'.$centralDomain;

            $userClass = config('auth.providers.tenant_users.model');

            $password = $this->password ?? 'password';

            $userClass::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $this->tenant->name.' Admin',
                    'password' => $password,
                    'email_verified_at' => now(),
                ]
            );
        });
    }
}
