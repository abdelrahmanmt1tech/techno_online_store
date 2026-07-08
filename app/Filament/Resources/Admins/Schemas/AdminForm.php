<?php

namespace App\Filament\Resources\Admins\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class AdminForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.admin_details'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('dashboard.name'))
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label(__('dashboard.email'))
                            ->email()
                            ->autocomplete('new-email')
                            ->unique(ignoreRecord: true),

                        TextInput::make('password')
                            ->label(__('dashboard.password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->required(fn ($record) => $record === null)
                            ->dehydrated(fn ($state) => filled($state)),

                        TextInput::make('password_confirmation')
                            ->label(__('dashboard.password_confirmation'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->required(fn ($record) => $record === null)
                            ->dehydrated(false),

                        Select::make('role')
                            ->label(__('dashboard.role_select'))
                            ->options(Role::where('guard_name', 'admin')->get()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record?->roles?->first()?->id))
                            ->dehydrated(false)
                            ->saveRelationshipsUsing(function ($component, $state, $record) {
                                if ($state) {
                                    $record->roles()->sync([$state]);
                                }
                            }),

                        Toggle::make('is_active')
                            ->label(__('dashboard.active'))
                            ->default(true),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
