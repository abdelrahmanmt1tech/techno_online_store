<?php

namespace App\Observers;

use App\Mail\TenantPackageMail;
use App\Models\TenantPackage;
use Illuminate\Support\Facades\Mail;

class TenantPackageObserver
{
    public function created(TenantPackage $package): void
    {
        $this->send($package, 'added');
    }

    public function updated(TenantPackage $package): void
    {
        if (! $package->wasChanged('status')) {
            return;
        }

        $action = match ($package->status) {
            'active' => 'activated',
            'cancelled' => 'deactivated',
            'expired' => 'expired',
            default => null,
        };

        if ($action) {
            $this->send($package, $action);
        }
    }

    public function deleted(TenantPackage $package): void
    {
        $this->send($package, 'cancelled');
    }

    protected function send(TenantPackage $package, string $action): void
    {
        $tenant = $package->tenant;

        if (! $tenant?->email) {
            return;
        }

        $packageName = $package->package?->getTranslation('name', app()->getLocale()) ?? '';

        Mail::to($tenant->email)->send(new TenantPackageMail(
            tenantName: $tenant->name,
            packageName: $packageName,
            action: $action,
            price: $package->price !== null ? number_format((float) $package->price, 2) : null,
            duration: $package->duration
                ? $package->duration.' '.__("dashboard.{$package->duration_type}")
                : null,
            expiresAt: $package->expires_at?->format('Y-m-d'),
        ));
    }
}
