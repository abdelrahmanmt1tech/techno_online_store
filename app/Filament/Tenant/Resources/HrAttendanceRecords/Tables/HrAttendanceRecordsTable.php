<?php

namespace App\Filament\Tenant\Resources\HrAttendanceRecords\Tables;

use App\Enums\Hr\AttendanceSource;
use App\Enums\Hr\AttendanceStatus;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class HrAttendanceRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('attendance_date', 'desc')
            ->columns([
                TextColumn::make('employee.full_name')->label(__('hr.fields.employee'))->searchable()->sortable(),
                TextColumn::make('attendance_date')->label(__('hr.fields.attendance_date'))->date()->sortable(),
                TextColumn::make('status')
                    ->label(__('hr.fields.status'))
                    ->formatStateUsing(fn ($state) => $state instanceof AttendanceStatus ? $state->label() : $state)
                    ->badge(),
                TextColumn::make('check_in_at')->label(__('hr.fields.check_in_at'))->dateTime('Y-m-d H:i')->toggleable(),
                TextColumn::make('check_out_at')->label(__('hr.fields.check_out_at'))->dateTime('Y-m-d H:i')->toggleable(),
                TextColumn::make('late_minutes')->label(__('hr.fields.late_minutes'))->sortable(),
                TextColumn::make('source')
                    ->label(__('hr.fields.source'))
                    ->formatStateUsing(fn ($state) => $state instanceof AttendanceSource ? $state->label() : $state)
                    ->badge()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label(__('hr.fields.employee'))
                    ->relationship('employee', 'full_name')
                    ->searchable()
                    ->native(false),
                SelectFilter::make('status')
                    ->label(__('hr.fields.status'))
                    ->options(collect(AttendanceStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all())
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => Auth::user()->can('hr.attendance.adjust')),
            ])
            ->emptyStateHeading(__('hr.empty.default'));
    }
}
