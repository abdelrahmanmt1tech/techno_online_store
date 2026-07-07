<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class RoleForm
{
    public static function groupedPermissions(): Collection
    {
        $permissions = Permission::query()
            ->where('guard_name', 'admin')
            ->get()
            ->keyBy('name');

        $ordered = collect();

        $groups = permissionsArray();

        foreach ($groups as $group) {
            $groupName = $group['name'];
            $groupPermissions = collect();

            foreach ($group['permissions'] as $permDef) {
                $key = $permDef['key'];
                if ($permissions->has($key)) {
                    $groupPermissions->push($permissions->get($key));
                    $permissions->forget($key);
                }
            }

            if ($groupPermissions->isNotEmpty()) {
                $ordered->push((object) [
                    'groupName' => $groupName,
                    'groupLabel' => __($groupName),
                    'permissions' => $groupPermissions,
                ]);
            }
        }

        if ($permissions->isNotEmpty()) {
            $ordered->push((object) [
                'groupName' => 'other',
                'groupLabel' => __('dashboard.other'),
                'permissions' => $permissions->values(),
            ]);
        }

        return $ordered;
    }

    public static function groupKey(string $groupName): string
    {
        return 'group_'.md5($groupName);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make(__('dashboard.role_details'))
                    ->schema(
                        [TextInput::make('name')
                            ->label(__('dashboard.role_name'))
                            ->unique(ignoreRecord: true)
                            ->required(), ]
                    ),

                Section::make(__('dashboard.permissions_section'))
                    ->schema(
                        self::groupedPermissions()
                            ->map(function ($group) {
                                $groupName = $group->groupName;
                                $groupLabel = $group->groupLabel;
                                $permissions = $group->permissions;
                                $groupKey = self::groupKey($groupName);
                                $permissionIds = $permissions->pluck('id')->map(fn ($id) => (string) $id)->values()->all();

                                return Fieldset::make($groupName)
                                    ->label($groupLabel)
                                    ->schema(
                                        [
                                            CheckboxList::make("permissions.{$groupKey}")
                                                ->label(__('dashboard.permissions_list'))
                                                ->options(
                                                    $permissions->mapWithKeys(fn ($per) => [
                                                        (string) $per->id => __($per->display_name ?? $per->name),
                                                    ])->toArray()
                                                )
                                                ->searchable()
                                                ->bulkToggleable()
                                                ->columns(4),
                                        ]
                                    )
                                    ->columns(1);
                            })->values()->toArray()
                    )
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }
}
