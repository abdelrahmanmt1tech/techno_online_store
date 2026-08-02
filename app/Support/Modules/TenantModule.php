<?php

namespace App\Support\Modules;

/**
 * Sellable merchant modules (each billed as its own subscription later).
 *
 * Plan/package entitlements are cancelled in favour of per-module subscriptions.
 * Wire real checks only inside {@see TenantModuleGate} — nowhere else.
 */
enum TenantModule: string
{
    case Store = 'store';
    case Pos = 'pos';
    case Crm = 'crm';
    case Accounting = 'accounting';

    public function label(): string
    {
        return match ($this) {
            self::Store => __('modules.store'),
            self::Pos => __('modules.pos'),
            self::Crm => __('modules.crm'),
            self::Accounting => __('modules.accounting'),
        };
    }
}
