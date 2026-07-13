<?php

namespace App\WhatsApp\Onboarding;

use App\Models\WhatsAppOnboardingSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class WhatsAppOnboardingSessionCleanup
{
    /**
     * Delete expired and terminal onboarding sessions older than retention.
     * Never deletes active non-expired sessions without completed_at/failed_at.
     */
    public function run(?int $retentionDays = null, ?Carbon $now = null): int
    {
        return $this->query($retentionDays, $now)->delete();
    }

    public function count(?int $retentionDays = null, ?Carbon $now = null): int
    {
        return $this->query($retentionDays, $now)->count();
    }

    protected function query(?int $retentionDays = null, ?Carbon $now = null): Builder
    {
        $retentionDays = max(1, $retentionDays ?? (int) config('whatsapp.onboarding.session_retention_days', 7));
        $now = $now ?? now();
        $cutoff = $now->copy()->subDays($retentionDays);

        return WhatsAppOnboardingSession::query()
            ->where(function ($query) use ($cutoff) {
                $query
                    ->where(function ($q) use ($cutoff) {
                        $q->whereNotNull('completed_at')
                            ->where('completed_at', '<=', $cutoff);
                    })
                    ->orWhere(function ($q) use ($cutoff) {
                        $q->whereNotNull('failed_at')
                            ->where('failed_at', '<=', $cutoff);
                    })
                    ->orWhere(function ($q) use ($cutoff) {
                        $q->whereNotNull('expires_at')
                            ->where('expires_at', '<=', $cutoff);
                    });
            })
            ->where(function ($query) use ($now) {
                $query
                    ->whereNotNull('completed_at')
                    ->orWhereNotNull('failed_at')
                    ->orWhere(function ($q) use ($now) {
                        $q->whereNotNull('expires_at')
                            ->where('expires_at', '<=', $now);
                    });
            });
    }
}
