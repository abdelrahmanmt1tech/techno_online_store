<?php

namespace App\Filament\Tenant\Resources\HrEmployees\Tables;

use App\Enums\Hr\EmploymentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HrEmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('full_name')
            ->columns([
                TextColumn::make('employee_number')->label(__('hr.fields.employee_number'))->searchable()->sortable(),
                TextColumn::make('full_name')->label(__('hr.fields.full_name'))->searchable()->sortable(),
                TextColumn::make('phone')->label(__('hr.fields.phone'))->searchable()->toggleable(),
                TextColumn::make('branch.name')->label(__('hr.fields.branch'))->toggleable(),
                TextColumn::make('department.name')->label(__('hr.fields.department'))->toggleable(),
                TextColumn::make('jobTitle.name')->label(__('hr.fields.job_title'))->toggleable(),
                TextColumn::make('employment_status')
                    ->label(__('hr.fields.employment_status'))
                    ->formatStateUsing(fn ($state) => $state instanceof EmploymentStatus ? $state->label() : $state)
                    ->badge(),
                TextColumn::make('base_salary')->label(__('hr.fields.base_salary'))->sortable(),
                ToggleColumn::make('is_active')->label(__('hr.fields.is_active')),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('hr.fields.branch'))
                    ->relationship('branch', 'name')
                    ->native(false),
                SelectFilter::make('department_id')
                    ->label(__('hr.fields.department'))
                    ->relationship('department', 'name')
                    ->native(false),
                SelectFilter::make('employment_status')
                    ->label(__('hr.fields.employment_status'))
                    ->options(collect(EmploymentStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all())
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
