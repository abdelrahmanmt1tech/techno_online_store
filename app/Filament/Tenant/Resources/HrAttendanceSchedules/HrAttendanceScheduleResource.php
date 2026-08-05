<?php

namespace App\Filament\Tenant\Resources\HrAttendanceSchedules;

use App\Filament\Tenant\Resources\HrAttendanceSchedules\Pages\CreateHrAttendanceSchedule;
use App\Filament\Tenant\Resources\HrAttendanceSchedules\Pages\EditHrAttendanceSchedule;
use App\Filament\Tenant\Resources\HrAttendanceSchedules\Pages\ListHrAttendanceSchedules;
use App\Filament\Tenant\Resources\HrAttendanceSchedules\Schemas\HrAttendanceScheduleForm;
use App\Filament\Tenant\Resources\HrAttendanceSchedules\Tables\HrAttendanceSchedulesTable;
use App\Models\Tenant\HrAttendanceSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class HrAttendanceScheduleResource extends Resource
{
    protected static ?string $model = HrAttendanceSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    protected static ?int $navigationSort = 502;

    public static function getNavigationGroup(): ?string
    {
        return __('hr.nav.hr');
    }

    public static function getNavigationLabel(): string
    {
        return __('hr.resources.attendance_schedules');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr.resources.attendance_schedules');
    }

    public static function getModelLabel(): string
    {
        return __('hr.resources.attendance_schedule');
    }

    public static function canViewAny(): bool
    {
        return tenant_module_enabled(\App\Support\Modules\TenantModule::Hr) && (Auth::user()->can('hr.schedules.view'));
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('hr.schedules.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('hr.schedules.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('hr.schedules.manage');
    }

    public static function form(Schema $schema): Schema
    {
        return HrAttendanceScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HrAttendanceSchedulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHrAttendanceSchedules::route('/'),
            'create' => CreateHrAttendanceSchedule::route('/create'),
            'edit' => EditHrAttendanceSchedule::route('/{record}/edit'),
        ];
    }
}
