<?php

namespace App\Http\Controllers\Api\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\StoreTenantRequest;
use App\Jobs\SeedTenantDatabase;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Tenant;
use App\Traits\ApiResponse;
use Carbon\Carbon;

class TenantController extends Controller
{
    use ApiResponse;

    public function store(StoreTenantRequest $request)
    {
        $data = $request->validated();

        $subdomain = $data['subdomain'] ?? null;
        $password = $data['password'] ?? null;
        unset($data['subdomain'], $data['password'], $data['password_confirmation']);

        $packageIds = collect([
            ...($data['package_ids'] ?? []),
            $data['package_id'] ?? null,
        ])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        unset($data['package_id'], $data['package_ids']);

        $startedAt = isset($data['started_at']) ? Carbon::parse($data['started_at']) : now();
        unset($data['started_at']);

        $centralDomain = parse_url(config('app.domain_url'), PHP_URL_HOST) ?? 'localhost';

        $tenant = Tenant::create($data);

        if ($subdomain) {
            $tenant->createDomain($subdomain.'.'.$centralDomain);
        }

        if (filled($packageIds)) {
            $packages = Package::whereIn('id', $packageIds)->get()->keyBy('id');

            foreach ($packageIds as $packageId) {
                $package = $packages->get($packageId);

                if (! $package) {
                    continue;
                }

                $price = $this->resolvePackagePrice($packageId, $tenant);

                $duration = $data['duration'] ?? $price?->duration ?? 1;
                $durationType = $data['duration_type'] ?? $price?->duration_type ?? 'month';
                $currencyId = $data['currency_id'] ?? $price?->currency_id;
                $amount = $data['price'] ?? $price?->price ?? 0;

                $trialEndsAt = $package->trials_duration
                    ? $startedAt->copy()->addDays($package->trials_duration)
                    : null;

                $tenant->packages()->create([
                    'package_id' => $packageId,
                    'price' => $amount,
                    'currency_id' => $currencyId,
                    'duration' => $duration,
                    'duration_type' => $durationType,
                    'started_at' => $startedAt,
                    'trial_ends_at' => $trialEndsAt,
                    'expires_at' => ($trialEndsAt ?? $startedAt)
                        ->copy()
                        ->{"add{$durationType}s"}($duration),
                    'status' => 'active',
                ]);
            }
        }

        app(SeedTenantDatabase::class, [
            'tenant' => $tenant,
            'password' => $password,
        ])->handle();

        return $this->createdResponse([
            'id' => $tenant->id,
            'name' => $tenant->name,
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'domain' => $subdomain ? $subdomain.'.'.$centralDomain : null,
        ], __('messages.resource_created_successfully'));
    }

    private function resolvePackagePrice(int $packageId, Tenant $tenant): ?PackagePrice
    {
        $query = PackagePrice::where('package_id', $packageId);

        $countryId = $tenant->country_id;
        $currencyId = $tenant->currency_id;

        if ($countryId && $currencyId) {
            $exact = (clone $query)->where('country_id', $countryId)->where('currency_id', $currencyId)->first();
            if ($exact) {
                return $exact;
            }
        }

        if ($countryId) {
            $byCountry = (clone $query)->where('country_id', $countryId)->first();
            if ($byCountry) {
                return $byCountry;
            }
        }

        if ($currencyId) {
            $byCurrency = (clone $query)->where('currency_id', $currencyId)->first();
            if ($byCurrency) {
                return $byCurrency;
            }
        }

        return (clone $query)->first();
    }
}
