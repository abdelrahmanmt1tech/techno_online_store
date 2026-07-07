<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label(__('dashboard.id'))->sortable(),
                TextColumn::make('name')->label(__('dashboard.role_name'))->searchable(),

            ])
            ->filters([])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()
                    ->visible(fn ($record) => Auth::user()->can('roles-and-permission.view')),
                EditAction::make()
                    ->visible(fn ($record) => $record->id != 1 && Auth::user()->can('roles-and-permission.update')),

                DeleteAction::make()
                    ->visible(fn ($record) => $record->id != 1 && Auth::user()->can('roles-and-permission.destroy'))
                    ->disabled(fn ($record) => $record->users()->where('guard_name', 'admin')->exists()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('roles-and-permission.destroy')),
                ]),
            ]);
    }
}
