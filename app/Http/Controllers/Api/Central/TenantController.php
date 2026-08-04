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
use Illuminate\Support\Arr;

class TenantController extends Controller
{
    use ApiResponse;

    public function store(StoreTenantRequest $request)
    {
        $data = $request->validated();

        $subdomain = $data['subdomain'] ?? null;
        $password = $data['password'] ?? null;
        $startedAt = isset($data['started_at']) ? Carbon::parse($data['started_at']) : now();
        $centralDomain = parse_url(config('app.domain_url'), PHP_URL_HOST) ?? 'localhost';

        $tenant = Tenant::create(Arr::except($data, [
            'subdomain',
            'password',
            'password_confirmation',
            'started_at',
            'packages',
        ]));

        if ($subdomain) {
            $tenant->createDomain($subdomain.'.'.$centralDomain);
        }

        foreach ($data['packages'] as $item) {
            $package = Package::find($item['package_id']);
            $price = PackagePrice::find($item['price_id']);

            $trialEndsAt = $package->trials_duration
                ? $startedAt->copy()->addDays($package->trials_duration)
                : null;

            $period = $item['period'] ?? 'monthly';
            $durationType = $period === 'yearly' ? 'year' : 'month';

            $tenant->packages()->create([
                'package_id' => $package->id,
                'price' => $period === 'yearly' ? $price->price_yearly : $price->price_monthly,
                'currency_id' => $price->currency_id,
                'duration' => 1,
                'duration_type' => $durationType,
                'started_at' => $startedAt,
                'trial_ends_at' => $trialEndsAt,
                'expires_at' => ($trialEndsAt ?? $startedAt)
                    ->copy()
                    ->{"add{$durationType}s"}(1),
                'status' => 'active',
            ]);
        }

        SeedTenantDatabase::dispatch($tenant, $password);

        return $this->createdResponse([
            'id' => $tenant->id,
            'name' => $tenant->name,
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'domain' => $subdomain ? $subdomain.'.'.$centralDomain : null,
        ], __('messages.resource_created_successfully'));
    }
}
