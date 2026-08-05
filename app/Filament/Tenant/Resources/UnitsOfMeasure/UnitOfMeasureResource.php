<?php

namespace App\Filament\Tenant\Resources\UnitsOfMeasure;

use App\Filament\Tenant\Resources\UnitsOfMeasure\Pages\CreateUnitOfMeasure;
use App\Filament\Tenant\Resources\UnitsOfMeasure\Pages\EditUnitOfMeasure;
use App\Filament\Tenant\Resources\UnitsOfMeasure\Pages\ListUnitsOfMeasure;
use App\Filament\Tenant\Resources\UnitsOfMeasure\Schemas\UnitOfMeasureForm;
use App\Filament\Tenant\Resources\UnitsOfMeasure\Tables\UnitsOfMeasureTable;
use App\Models\Tenant\UnitOfMeasure;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UnitOfMeasureResource extends Resource
{
    protected static ?string $model = UnitOfMeasure::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Scale;

    protected static ?int $navigationSort = 302;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.units_of_measure');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.units_of_measure');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.unit_of_measure');
    }

    public static function canViewAny(): bool
    {
        return tenant_module_enabled(\App\Support\Modules\TenantModule::Pos) && (Auth::user()->can('erp.uom.view'));
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('erp.uom.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('erp.uom.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('erp.uom.manage');
    }

    public static function form(Schema $schema): Schema
    {
        return UnitOfMeasureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitsOfMeasureTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnitsOfMeasure::route('/'),
            'create' => CreateUnitOfMeasure::route('/create'),
            'edit' => EditUnitOfMeasure::route('/{record}/edit'),
        ];
    }
}
