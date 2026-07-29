<?php

namespace App\Filament\Tenant\Resources\HrAttendanceLocations;

use App\Filament\Tenant\Resources\HrAttendanceLocations\Pages\CreateHrAttendanceLocation;
use App\Filament\Tenant\Resources\HrAttendanceLocations\Pages\EditHrAttendanceLocation;
use App\Filament\Tenant\Resources\HrAttendanceLocations\Pages\ListHrAttendanceLocations;
use App\Filament\Tenant\Resources\HrAttendanceLocations\Schemas\HrAttendanceLocationForm;
use App\Filament\Tenant\Resources\HrAttendanceLocations\Tables\HrAttendanceLocationsTable;
use App\Models\Tenant\HrAttendanceLocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class HrAttendanceLocationResource extends Resource
{
    protected static ?string $model = HrAttendanceLocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::MapPin;

    protected static ?int $navigationSort = 503;

    public static function getNavigationGroup(): ?string
    {
        return __('hr.nav.hr');
    }

    public static function getNavigationLabel(): string
    {
        return __('hr.resources.attendance_locations');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr.resources.attendance_locations');
    }

    public static function getModelLabel(): string
    {
        return __('hr.resources.attendance_location');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('hr.locations.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('hr.locations.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('hr.locations.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('hr.locations.manage');
    }

    public static function form(Schema $schema): Schema
    {
        return HrAttendanceLocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HrAttendanceLocationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHrAttendanceLocations::route('/'),
            'create' => CreateHrAttendanceLocation::route('/create'),
            'edit' => EditHrAttendanceLocation::route('/{record}/edit'),
        ];
    }
}
