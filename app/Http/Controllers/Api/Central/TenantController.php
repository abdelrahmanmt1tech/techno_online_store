<?php

namespace App\Http\Controllers\Api\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Jobs\SeedTenantDatabase;
use App\Models\Tenant;
use App\Traits\ApiResponse;

class TenantController extends Controller
{
    use ApiResponse;

    public function store(StoreTenantRequest $request)
    {
        $data = $request->validated();

        $subdomain = $data['subdomain'] ?? null;
        $password = $data['password'] ?? null;
        unset($data['subdomain'], $data['password'], $data['password_confirmation']);

        $planData = [
            'plan_id' => $data['plan_id'] ?? null,
            'price' => $data['price'] ?? 0,
            'currency' => $data['currency'] ?? 'SAR',
            'started_at' => $data['started_at'] ?? now(),
            'expires_at' => $data['expires_at'] ?? null,
        ];
        unset($data['plan_id'], $data['price'], $data['currency'], $data['started_at'], $data['expires_at']);

        $centralDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        $tenant = Tenant::create($data);

        if ($subdomain) {
            $tenant->createDomain($subdomain.'.'.$centralDomain);
        }

        if ($planData['plan_id']) {
            $tenant->subscriptions()->create($planData);
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
}
