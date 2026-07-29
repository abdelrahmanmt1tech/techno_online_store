<?php

namespace App\Filament\Tenant\Resources\HrAttendanceLocations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HrAttendanceLocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label(__('hr.fields.name'))->searchable()->sortable(),
                TextColumn::make('branch.name')->label(__('hr.fields.branch'))->toggleable(),
                TextColumn::make('latitude')->label(__('hr.fields.latitude'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('longitude')->label(__('hr.fields.longitude'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('allowed_radius_meters')->label(__('hr.fields.allowed_radius_meters'))->sortable(),
                ToggleColumn::make('is_active')->label(__('hr.fields.is_active')),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('hr.fields.branch'))
                    ->relationship('branch', 'name')
                    ->native(false),
                SelectFilter::make('is_active')
                    ->label(__('hr.fields.is_active'))
                    ->options([
                        '1' => __('dashboard.active'),
                        '0' => __('dashboard.inactive'),
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('hr.empty.default'));
    }
}
