<?php

namespace App\Filament\Tenant\Resources\HrPayrollPeriods\Tables;

use App\Enums\Hr\PayrollPeriodStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HrPayrollPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('hr.fields.name'))->searchable()->sortable(),
                TextColumn::make('start_date')->label(__('hr.fields.start_date'))->date()->sortable(),
                TextColumn::make('end_date')->label(__('hr.fields.end_date'))->date()->sortable(),
                TextColumn::make('status')
                    ->label(__('hr.fields.status'))
                    ->formatStateUsing(fn ($state) => $state instanceof PayrollPeriodStatus ? $state->label() : $state)
                    ->badge(),
                TextColumn::make('generated_at')->label(__('hr.fields.generated_at'))->dateTime('Y-m-d H:i')->toggleable(),
                TextColumn::make('approved_at')->label(__('hr.fields.approved_at'))->dateTime('Y-m-d H:i')->toggleable(),
                TextColumn::make('paid_at')->label(__('hr.fields.paid_at'))->dateTime('Y-m-d H:i')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('hr.fields.status'))
                    ->options(collect(PayrollPeriodStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all())
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading(__('hr.empty.default'));
    }
}
