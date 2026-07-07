<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public function mount(): void
    {
        parent::mount();
        StorePermissionsArray();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] = 'admin';
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
