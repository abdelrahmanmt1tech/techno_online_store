<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use App\Jobs\SeedTenantDatabase;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $subdomain = $data['subdomain'] ?? null;
        $password = $data['password'] ?? null;
        unset($data['subdomain'], $data['password'], $data['password_confirmation']);

        $centralDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        $tenantClass = static::getModel();
        $tenant = new $tenantClass($data);
        $tenant->save();

        if ($subdomain) {
            $tenant->createDomain($subdomain.'.'.$centralDomain);
        }

        app(SeedTenantDatabase::class, [
            'tenant' => $tenant,
            'password' => $password,
        ])->handle();

        return $tenant;
    }
}
