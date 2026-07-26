<?php

namespace App\Filament\Tenant\Resources\Roles\Pages;

use App\Filament\Tenant\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    public function mount(): void
    {
        parent::mount();

        StoreTenantPermissionsArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->url(RoleResource::getUrl('create'))
                ->visible(fn () => Auth::user()->can('roles-and-permission.create')),
        ];
    }
}
