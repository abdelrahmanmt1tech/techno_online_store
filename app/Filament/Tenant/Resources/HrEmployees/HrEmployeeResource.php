<?php

namespace App\Filament\Tenant\Resources\HrEmployees;

use App\Filament\Tenant\Resources\HrEmployees\Pages\CreateHrEmployee;
use App\Filament\Tenant\Resources\HrEmployees\Pages\EditHrEmployee;
use App\Filament\Tenant\Resources\HrEmployees\Pages\ListHrEmployees;
use App\Filament\Tenant\Resources\HrEmployees\Schemas\HrEmployeeForm;
use App\Filament\Tenant\Resources\HrEmployees\Tables\HrEmployeesTable;
use App\Models\Tenant\HrEmployee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class HrEmployeeResource extends Resource
{
    protected static ?string $model = HrEmployee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Identification;

    protected static ?int $navigationSort = 504;

    public static function getNavigationGroup(): ?string
    {
        return __('hr.nav.hr');
    }

    public static function getNavigationLabel(): string
    {
        return __('hr.resources.employees');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr.resources.employees');
    }

    public static function getModelLabel(): string
    {
        return __('hr.resources.employee');
    }

    public static function canViewAny(): bool
    {
        return tenant_module_enabled(\App\Support\Modules\TenantModule::Hr) && (Auth::user()->can('hr.employees.view'));
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('hr.employees.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('hr.employees.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('hr.employees.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return HrEmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HrEmployeesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHrEmployees::route('/'),
            'create' => CreateHrEmployee::route('/create'),
            'edit' => EditHrEmployee::route('/{record}/edit'),
        ];
    }
}
