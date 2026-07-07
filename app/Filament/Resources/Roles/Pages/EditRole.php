<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $selectedIds = $this->record->permissions()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        $permissionsState = [];

        RoleForm::groupedPermissions()->each(function ($group) use (&$permissionsState, $selectedIds) {
            $groupKey = RoleForm::groupKey($group->groupName);
            $groupIds = $group->permissions->pluck('id')->map(fn ($id) => (string) $id)->toArray();
            $permissionsState[$groupKey] = array_values(array_intersect($selectedIds, $groupIds));
        });

        $this->form->fill([
            ...$this->record->toArray(),
            'permissions' => $permissionsState,
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['permissions']);
        unset($data['permissions_select_all']);

        return $data;
    }

    protected function afterSave(): void
    {
        $permissionsState = $this->form->getState()['permissions'] ?? [];
        $permissions = collect($permissionsState)->flatten()->filter()->unique()->values()->toArray();
        $this->record->syncPermissions($permissions);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn () => $this->record->id != 1 && Auth::user()->can('roles-and-permission.view')),
            DeleteAction::make()
                ->visible(fn () => $this->record->id != 1 && Auth::user()->can('roles-and-permission.destroy'))
                ->disabled(fn () => $this->record->users()->where('guard_name', 'admin')->exists()),
        ];
    }
}
