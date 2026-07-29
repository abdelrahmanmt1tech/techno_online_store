<?php

namespace App\Filament\Tenant\Resources\HrAttendanceSchedules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HrAttendanceSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label(__('hr.fields.name'))->searchable()->sortable(),
                IconColumn::make('is_default')->label(__('hr.fields.is_default'))->boolean(),
                TextColumn::make('late_grace_minutes')->label(__('hr.fields.late_grace_minutes'))->toggleable(),
                TextColumn::make('days_count')->label(__('hr.fields.schedule_days'))->counts('days')->toggleable(),
                ToggleColumn::make('is_active')->label(__('hr.fields.is_active')),
            ])
            ->filters([
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
