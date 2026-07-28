<?php

namespace App\Filament\Tenant\Resources\TenantUsers\Pages;

use App\Filament\Tenant\Resources\TenantUsers\TenantUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListTenantUsers extends ListRecords
{
    protected static string $resource = TenantUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => Auth::user()->can('tenant-users.create')),
        ];
    }
}
