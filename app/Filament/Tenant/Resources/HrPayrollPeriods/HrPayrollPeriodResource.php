<?php

namespace App\Filament\Tenant\Resources\HrPayrollPeriods;

use App\Enums\Hr\PayrollPeriodStatus;
use App\Filament\Tenant\Resources\HrPayrollPeriods\Pages\CreateHrPayrollPeriod;
use App\Filament\Tenant\Resources\HrPayrollPeriods\Pages\EditHrPayrollPeriod;
use App\Filament\Tenant\Resources\HrPayrollPeriods\Pages\ListHrPayrollPeriods;
use App\Filament\Tenant\Resources\HrPayrollPeriods\Pages\ViewHrPayrollPeriod;
use App\Filament\Tenant\Resources\HrPayrollPeriods\RelationManagers\EmployeesRelationManager;
use App\Filament\Tenant\Resources\HrPayrollPeriods\Schemas\HrPayrollPeriodForm;
use App\Filament\Tenant\Resources\HrPayrollPeriods\Tables\HrPayrollPeriodsTable;
use App\Models\Tenant\HrPayrollPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class HrPayrollPeriodResource extends Resource
{
    protected static ?string $model = HrPayrollPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static ?int $navigationSort = 506;

    public static function getNavigationGroup(): ?string
    {
        return __('hr.nav.hr');
    }

    public static function getNavigationLabel(): string
    {
        return __('hr.resources.payroll_periods');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr.resources.payroll_periods');
    }

    public static function getModelLabel(): string
    {
        return __('hr.resources.payroll_period');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('hr.payroll.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('hr.payroll.generate');
    }

    public static function canEdit(Model $record): bool
    {
        /** @var HrPayrollPeriod $record */
        return Auth::user()->can('hr.payroll.generate') && $record->status === PayrollPeriodStatus::Draft;
    }

    public static function canDelete(Model $record): bool
    {
        /** @var HrPayrollPeriod $record */
        return Auth::user()->can('hr.payroll.generate') && $record->status === PayrollPeriodStatus::Draft;
    }

    public static function form(Schema $schema): Schema
    {
        return HrPayrollPeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HrPayrollPeriodsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EmployeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHrPayrollPeriods::route('/'),
            'create' => CreateHrPayrollPeriod::route('/create'),
            'view' => ViewHrPayrollPeriod::route('/{record}'),
            'edit' => EditHrPayrollPeriod::route('/{record}/edit'),
        ];
    }
}
