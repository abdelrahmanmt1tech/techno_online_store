<?php

namespace App\Jobs;

use App\Mail\TenantWelcomeMail;
use App\Models\Tenant;
use App\Models\TenantUserCredential;
use Database\Seeders\TenantDataSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SeedTenantDatabase implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Tenant $tenant,
        protected ?string $password = null,
    ) {}

    public function handle(): void
    {
        $email = null;
        $password = $this->password ?? 'password';

        $this->tenant->run(function () use (&$email, $password) {
            $centralDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
            $subdomain = str_replace('.'.$centralDomain, '', $this->tenant->domains()->first()?->domain ?? '');

            $email = $this->tenant->email ?? 'admin@'.($subdomain ?: $this->tenant->id).'.'.$centralDomain;

            $userClass = config('auth.providers.tenant_users.model');

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

            DB::transaction(function (): void {
                (new TenantDataSeeder)->run();
            });
        });

        if ($email) {
            TenantUserCredential::updateOrCreate(
                ['email' => $email],
                ['tenant_id' => $this->tenant->id]
            );

            $this->sendWelcomeMail($email, $password);
        }
    }

    protected function sendWelcomeMail(string $email, string $password): void
    {
        $domain = $this->tenant->domains()->first()?->domain;

        if (! $domain) {
            return;
        }

        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'https';

        Mail::to($email)->send(new TenantWelcomeMail(
            tenantName: $this->tenant->name,
            email: $email,
            password: $password,
            loginUrl: $scheme.'://'.$domain.'/app/login',
        ));
    }
}
