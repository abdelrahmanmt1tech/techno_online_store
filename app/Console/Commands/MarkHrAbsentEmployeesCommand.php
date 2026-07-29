<?php

namespace App\Console\Commands;

use App\Actions\Hr\MarkAbsentEmployeesAction;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarkHrAbsentEmployeesCommand extends Command
{
    protected $signature = 'hr:mark-absent {--date= : Y-m-d date to process}';

    protected $description = 'Mark active employees as absent when they missed check-in after schedule end';

    public function handle(MarkAbsentEmployeesAction $action): int
    {
        $date = $this->option('date') ? now()->parse((string) $this->option('date')) : null;
        $total = 0;

        Tenant::query()
            ->active()
            ->each(function (Tenant $tenant) use ($action, $date, &$total) {
                try {
                    $tenant->run(function () use ($action, $date, $tenant, &$total) {
                        $count = $action->execute($date);
                        $total += $count;
                        $this->line("Tenant {$tenant->id}: marked {$count} absent");
                    });
                } catch (\Throwable $e) {
                    Log::error('hr:mark-absent failed for tenant', [
                        'tenant_id' => (string) $tenant->id,
                        'message' => $e->getMessage(),
                    ]);
                    $this->line("Tenant {$tenant->id}: failed (".get_class($e).')');
                }
            });

        $this->info("Done. Total absent records: {$total}");

        return self::SUCCESS;
    }
}
