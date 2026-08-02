<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

/**
 * SaaS feature gates are not used in techno_online_store.
 * Always pass so CRM resources remain usable during development.
 */
trait HasTenantFeatureAccess
{
    public static function passesTenantFeatureGate(): bool
    {
        return true;
    }
}
