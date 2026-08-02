<?php

namespace App\Filament\Tenant\Resources\FinancialPeriods;

use App\Filament\Tenant\Resources\FinancialPeriods\Pages\CreateFinancialPeriod;
use App\Filament\Tenant\Resources\FinancialPeriods\Pages\CreateOpeningEntry;
use App\Filament\Tenant\Resources\FinancialPeriods\Pages\EditFinancialPeriod;
use App\Filament\Tenant\Resources\FinancialPeriods\Pages\EditOpeningEntry;
use App\Filament\Tenant\Resources\FinancialPeriods\Pages\ListFinancialPeriods;
use App\Filament\Tenant\Resources\FinancialPeriods\Pages\ListOpeningEntries;
use App\Filament\Tenant\Resources\FinancialPeriods\Pages\ViewFinancialPeriod;
use App\Filament\Tenant\Resources\FinancialPeriods\Pages\ViewFinancialPeriodBalances;
use App\Filament\Tenant\Resources\FinancialPeriods\Schemas\FinancialPeriodForm;
use App\Filament\Tenant\Resources\FinancialPeriods\Schemas\FinancialPeriodInfolist;
use App\Filament\Tenant\Resources\FinancialPeriods\Tables\FinancialPeriodsTable;
use App\Models\Tenant\FinancialPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class FinancialPeriodResource extends Resource
{
    protected static ?string $model = FinancialPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = null;
    protected static ?string $pluralModelLabel = null;
    protected static ?string $modelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.resources.financial_period.nav');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.resources.financial_period.plural_model_label');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.resources.financial_period.model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('financial_periods.view') ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('financial_periods.view') ?? false;
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('financial_periods.view') ?? false;
    }

    public static function canView($record): bool
    {
        return Auth::user()?->can('financial_periods.show') ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('financial_periods.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->can('financial_periods.update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->can('financial_periods.delete') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return FinancialPeriodForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FinancialPeriodInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinancialPeriodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinancialPeriods::route('/'),
            'create' => CreateFinancialPeriod::route('/create'),
            'view' => ViewFinancialPeriod::route('/{record}'),
            'edit' => EditFinancialPeriod::route('/{record}/edit'),
            'opening-entries' => ListOpeningEntries::route('/{record}/opening-entries'),
            'opening-entry' => CreateOpeningEntry::route('/{record}/opening-entry'),
            'opening-entry-edit' => EditOpeningEntry::route('/{record}/opening-entries/{operation}/edit'),
            'balances' => ViewFinancialPeriodBalances::route('/{record}/balances'),
        ];
    }
}
