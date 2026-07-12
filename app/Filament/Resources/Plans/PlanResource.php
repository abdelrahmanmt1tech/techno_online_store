<?php

namespace App\Filament\Resources\Plans;

use App\Filament\Resources\Plans\Pages\CreatePlan;
use App\Filament\Resources\Plans\Pages\EditPlan;
use App\Filament\Resources\Plans\Pages\ListPlans;
use App\Filament\Resources\Plans\Schemas\PlanForm;
use App\Filament\Resources\Plans\Tables\PlansTable;
use App\Models\Plan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.plans');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.plans');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.plan');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('plans.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('plans.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('plans.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('plans.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return PlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlans::route('/'),
            'create' => CreatePlan::route('/create'),
            'edit' => EditPlan::route('/{record}/edit'),
        ];
    }
}
