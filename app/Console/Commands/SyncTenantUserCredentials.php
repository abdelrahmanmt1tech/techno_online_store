<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantUserCredential;
use Illuminate\Console\Command;

class SyncTenantUserCredentials extends Command
{
    protected $signature = 'tenants:sync-credentials';
    protected $description = 'Sync tenant user credentials for all existing tenants';

    public function handle(): int
    {
        $tenants = Tenant::all();
        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        $count = 0;

        foreach ($tenants as $tenant) {
            $email = $tenant->email;

            if ($email) {
                TenantUserCredential::updateOrCreate(
                    ['email' => $email],
                    ['tenant_id' => $tenant->id]
                );
                $count++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Synced {$count} credentials.");

        return self::SUCCESS;
    }
}
