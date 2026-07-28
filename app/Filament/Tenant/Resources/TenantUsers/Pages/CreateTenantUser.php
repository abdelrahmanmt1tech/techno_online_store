<?php

namespace App\Filament\Tenant\Resources\TenantUsers\Pages;

use App\Filament\Tenant\Resources\TenantUsers\TenantUserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTenantUser extends CreateRecord
{
    protected static string $resource = TenantUserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['is_active'] ??= true;

        return static::getModel()::create($data);
    }
}
