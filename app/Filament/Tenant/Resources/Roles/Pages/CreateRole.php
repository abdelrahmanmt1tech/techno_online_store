<?php

namespace App\Filament\Tenant\Resources\Roles\Pages;

use App\Filament\Tenant\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public function mount(): void
    {
        parent::mount();
        StoreTenantPermissionsArray();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] = 'tenant';
        unset($data['permissions']);
        unset($data['permissions_select_all']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $permissionsState = $this->form->getState()['permissions'] ?? [];
        $permissions = collect($permissionsState)->flatten()->filter()->unique()->values()->toArray();
        $this->record->syncPermissions($permissions);
    }
}
