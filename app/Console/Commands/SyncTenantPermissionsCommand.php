<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SyncTenantPermissionsCommand extends Command
{
    protected $signature = 'tenants:sync-permissions {--migrate : Run tenant migrations before syncing permissions}';

    protected $description = 'Sync tenant permissions and Store Admin role for all existing tenants';

    public function handle(): int
    {
        if ($this->option('migrate')) {
            $this->info('Running tenant migrations...');
            Artisan::call('tenants:migrate', ['--force' => true]);
            $this->line(Artisan::output());
        }

        $tenants = Tenant::query()->get();
        $count = 0;

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant, &$count) {
                StoreTenantPermissionsArray();
                $role = setupStoreAdminRole();

                $userClass = config('auth.providers.tenant_users.model');
                $user = $userClass::query()->orderBy('id')->first();

                if ($user !== null) {
                    $user->assignRole($role);
                }

                $count++;
                $this->line("Synced permissions for tenant: {$tenant->id}");
            });
        }

        $this->info("Completed for {$count} tenant(s).");

        return self::SUCCESS;
    }
}
