<?php

namespace App\Filament\Tenant\Resources\HrDepartments;

use App\Filament\Tenant\Resources\HrDepartments\Pages\CreateHrDepartment;
use App\Filament\Tenant\Resources\HrDepartments\Pages\EditHrDepartment;
use App\Filament\Tenant\Resources\HrDepartments\Pages\ListHrDepartments;
use App\Filament\Tenant\Resources\HrDepartments\Schemas\HrDepartmentForm;
use App\Filament\Tenant\Resources\HrDepartments\Tables\HrDepartmentsTable;
use App\Models\Tenant\HrDepartment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class HrDepartmentResource extends Resource
{
    protected static ?string $model = HrDepartment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingLibrary;

    protected static ?int $navigationSort = 500;

    public static function getNavigationGroup(): ?string
    {
        return __('hr.nav.hr');
    }

    public static function getNavigationLabel(): string
    {
        return __('hr.resources.departments');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr.resources.departments');
    }

    public static function getModelLabel(): string
    {
        return __('hr.resources.department');
    }

    public static function canViewAny(): bool
    {
        return tenant_module_enabled(\App\Support\Modules\TenantModule::Hr) && (Auth::user()->can('hr.departments.manage'));
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('hr.departments.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('hr.departments.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('hr.departments.manage');
    }

    public static function form(Schema $schema): Schema
    {
        return HrDepartmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HrDepartmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHrDepartments::route('/'),
            'create' => CreateHrDepartment::route('/create'),
            'edit' => EditHrDepartment::route('/{record}/edit'),
        ];
    }
}
