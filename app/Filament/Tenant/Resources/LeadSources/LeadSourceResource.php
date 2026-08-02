<?php

namespace App\Filament\Tenant\Resources\LeadSources;

use App\Filament\Tenant\Resources\LeadSources\Pages\CreateLeadSource;
use App\Filament\Tenant\Resources\LeadSources\Pages\EditLeadSource;
use App\Filament\Tenant\Resources\LeadSources\Pages\ListLeadSources;
use App\Filament\Tenant\Resources\LeadSources\Schemas\LeadSourceForm;
use App\Filament\Tenant\Resources\LeadSources\Tables\LeadSourcesTable;
use App\Models\Tenant\LeadSource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class LeadSourceResource extends Resource
{
    protected static ?string $model = \App\Models\Tenant\LeadSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Funnel;

    protected static ?string $recordTitleAttribute = 'name';


    protected static ?int $navigationSort = 110;

    protected static ?string $navigationLabel = null;
    protected static ?string $pluralModelLabel = null;
    protected static ?string $modelLabel = null;




    public static function getNavigationLabel(): string
    {
        return __('dashboard.sidebar.lead_sources');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.resources.lead_source.plural_model_label');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.resources.lead_source.model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.resources.lead_source.nav_group');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('lead_sources.view') ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('lead_sources.view') ?? false;
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('lead_sources.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('lead_sources.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->can('lead_sources.update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->can('lead_sources.delete') ?? false;
    }

    public static function canRestore($record): bool
    {
        return Auth::user()?->can('lead_sources.restore') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return Auth::user()?->can('lead_sources.force_delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->can('lead_sources.delete_bulk') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return Auth::user()?->can('lead_sources.restore_bulk') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return Auth::user()?->can('lead_sources.force_delete_bulk') ?? false;
    }





    public static function form(Schema $schema): Schema
    {
        return LeadSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadSourcesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeadSources::route('/'),
//            'create' => CreateLeadSource::route('/create'),
//            'edit' => EditLeadSource::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withReportingStats();
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

}
