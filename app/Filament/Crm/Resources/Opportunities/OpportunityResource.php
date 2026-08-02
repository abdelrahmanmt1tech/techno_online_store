<?php

namespace App\Filament\Crm\Resources\Opportunities;

use App\Filament\Crm\CrmResource;
use App\Filament\Crm\Resources\Opportunities\Pages\CreateOpportunity;
use App\Filament\Crm\Resources\Opportunities\Pages\EditOpportunity;
use App\Filament\Crm\Resources\Opportunities\Pages\ListOpportunities;
use App\Filament\Crm\Resources\Opportunities\Pages\ViewOpportunity;
use App\Filament\Crm\Resources\Opportunities\RelationManagers\OpportunityAssignmentLogsRelationManager;
use App\Filament\Crm\Resources\Opportunities\RelationManagers\OpportunityCommissionsRelationManager;
use App\Filament\Crm\Resources\Opportunities\RelationManagers\OpportunityFollowUpsRelationManager;
use App\Filament\Crm\Resources\Opportunities\RelationManagers\OpportunityNotesRelationManager;
use App\Filament\Crm\Resources\Opportunities\RelationManagers\OpportunityStageLogsRelationManager;
use App\Filament\Crm\Resources\Opportunities\Schemas\OpportunityForm;
use App\Filament\Crm\Resources\Opportunities\Schemas\OpportunityInfolist;
use App\Filament\Crm\Resources\Opportunities\Tables\OpportunitiesTable;
use App\Models\Tenant\Opportunity;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class OpportunityResource extends CrmResource
{
    protected static ?string $model = Opportunity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.opportunity.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.opportunity.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.opportunity.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.pipeline');
    }

    public static function canViewAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_opportunities.view') ?? false;
    }

    public static function canCreateByPermission(): bool
    {
        return Auth::user()?->can('crm_opportunities.create') ?? false;
    }

    public static function canEditByPermission($record): bool
    {
        return Auth::user()?->can('crm_opportunities.update') ?? false;
    }

    public static function canDeleteByPermission($record): bool
    {
        return Auth::user()?->can('crm_opportunities.delete') ?? false;
    }

    public static function canRestoreByPermission($record): bool
    {
        return Auth::user()?->can('crm_opportunities.restore') ?? false;
    }

    public static function canForceDeleteByPermission($record): bool
    {
        return Auth::user()?->can('crm_opportunities.force_delete') ?? false;
    }

    public static function canDeleteAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_opportunities.delete_bulk') ?? false;
    }

    public static function canRestoreAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_opportunities.restore_bulk') ?? false;
    }

    public static function canForceDeleteAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_opportunities.force_delete_bulk') ?? false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'client.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Opportunity $record */
        return [
            __('crm.fields.client') => $record->client?->name,
            __('crm.fields.stage') => $record->opportunityStage?->name,
            __('crm.fields.amount') => $record->amount,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['latestNote', 'opportunityStage', 'client', 'assignedTo', 'branch']);
    }

    public static function form(Schema $schema): Schema
    {
        return OpportunityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OpportunityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpportunitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OpportunityFollowUpsRelationManager::class,
            OpportunityCommissionsRelationManager::class,
            OpportunityNotesRelationManager::class,
            OpportunityStageLogsRelationManager::class,
            OpportunityAssignmentLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOpportunities::route('/'),
            'create' => CreateOpportunity::route('/create'),
            'view' => ViewOpportunity::route('/{record}'),
            'edit' => EditOpportunity::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
