<?php

namespace App\Filament\Tenant\Resources\HrAttendanceRecords;

use App\Filament\Tenant\Resources\HrAttendanceRecords\Pages\EditHrAttendanceRecord;
use App\Filament\Tenant\Resources\HrAttendanceRecords\Pages\ListHrAttendanceRecords;
use App\Filament\Tenant\Resources\HrAttendanceRecords\Schemas\HrAttendanceRecordForm;
use App\Filament\Tenant\Resources\HrAttendanceRecords\Tables\HrAttendanceRecordsTable;
use App\Models\Tenant\HrAttendanceRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class HrAttendanceRecordResource extends Resource
{
    protected static ?string $model = HrAttendanceRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;

    protected static ?int $navigationSort = 505;

    public static function getNavigationGroup(): ?string
    {
        return __('hr.nav.hr');
    }

    public static function getNavigationLabel(): string
    {
        return __('hr.resources.attendance_records');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr.resources.attendance_records');
    }

    public static function getModelLabel(): string
    {
        return __('hr.resources.attendance_record');
    }

    public static function canViewAny(): bool
    {
        return tenant_module_enabled(\App\Support\Modules\TenantModule::Hr) && (Auth::user()->can('hr.attendance.view'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('hr.attendance.adjust');
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return HrAttendanceRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HrAttendanceRecordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHrAttendanceRecords::route('/'),
            'edit' => EditHrAttendanceRecord::route('/{record}/edit'),
        ];
    }
}
