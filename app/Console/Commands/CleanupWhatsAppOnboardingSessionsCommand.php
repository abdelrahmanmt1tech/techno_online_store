<?php

namespace App\Console\Commands;

use App\WhatsApp\Onboarding\WhatsAppOnboardingSessionCleanup;
use Illuminate\Console\Command;

class CleanupWhatsAppOnboardingSessionsCommand extends Command
{
    protected $signature = 'whatsapp:onboarding-sessions:cleanup
                            {--days= : Override retention days (default from config)}
                            {--dry-run : Show how many sessions would be deleted without deleting}';

    protected $description = 'Delete expired/terminal WhatsApp Embedded Signup onboarding sessions older than retention';

    public function handle(WhatsAppOnboardingSessionCleanup $cleanup): int
    {
        $daysOption = $this->option('days');
        $retentionDays = filled($daysOption)
            ? (int) $daysOption
            : (int) config('whatsapp.onboarding.session_retention_days', 7);

        if ($this->option('dry-run')) {
            $count = $cleanup->count($retentionDays);
            $this->info("Dry run: {$count} onboarding session(s) would be deleted (retention {$retentionDays} day(s)).");

            return self::SUCCESS;
        }

        $deleted = $cleanup->run($retentionDays);

        $this->info("Deleted {$deleted} WhatsApp onboarding session(s) older than {$retentionDays} day(s).");

        return self::SUCCESS;
    }
}
