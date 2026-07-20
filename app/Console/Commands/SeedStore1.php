<?php

namespace App\Console\Commands;

use App\Jobs\SeedTenantDatabase;
use App\Models\Tenant;
use Illuminate\Console\Command;

class SeedStore1 extends Command
{
    protected $signature = 'tenant:seed-store1';

    protected $description = 'Create store1 tenant with demo data and credentials store1@admin.com / 123456789';

    public function handle(): int
    {
        $centralDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        $tenant = Tenant::firstOrCreate(
            ['email' => 'store1@admin.com'],
            [
                'name' => 'Store 1',
                'phone' => '01000000000',
                'country_name' => 'Egypt',
                'currency_code' => 'EGP',
                'is_active' => true,
            ]
        );

        if ($tenant->wasRecentlyCreated) {
            $tenant->createDomain('store1.'.$centralDomain);
            $this->info("Tenant created: store1.{$centralDomain}");
        } else {
            $this->info('Tenant already exists, skipping creation.');
        }

        app(SeedTenantDatabase::class, [
            'tenant' => $tenant,
            'password' => '123456789',
        ])->handle();

        $this->info('Done. Credentials: store1@admin.com / 123456789');

        return self::SUCCESS;
    }
}
