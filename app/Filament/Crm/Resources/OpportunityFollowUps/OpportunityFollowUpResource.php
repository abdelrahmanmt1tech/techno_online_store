<?php

namespace App\Filament\Crm\Resources\OpportunityFollowUps;

use App\Filament\Crm\CrmResource;
use App\Filament\Crm\Resources\OpportunityFollowUps\Pages\CreateOpportunityFollowUp;
use App\Filament\Crm\Resources\OpportunityFollowUps\Pages\EditOpportunityFollowUp;
use App\Filament\Crm\Resources\OpportunityFollowUps\Pages\ListOpportunityFollowUps;
use App\Filament\Crm\Resources\OpportunityFollowUps\Pages\ViewOpportunityFollowUp;
use App\Filament\Crm\Resources\OpportunityFollowUps\RelationManagers\OpportunityFollowUpChildrenRelationManager;
use App\Filament\Crm\Resources\OpportunityFollowUps\RelationManagers\OpportunityFollowUpNotesRelationManager;
use App\Filament\Crm\Resources\OpportunityFollowUps\Schemas\OpportunityFollowUpForm;
use App\Filament\Crm\Resources\OpportunityFollowUps\Schemas\OpportunityFollowUpInfolistSchema;
use App\Filament\Crm\Resources\OpportunityFollowUps\Tables\OpportunityFollowUpsTable;
use App\Models\Tenant\OpportunityFollowUp;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class OpportunityFollowUpResource extends CrmResource
{
    protected static ?string $model = OpportunityFollowUp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::PhoneArrowUpRight;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.follow_up.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.follow_up.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.follow_up.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.pipeline');
    }

    public static function canViewAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_ups.view') ?? false;
    }

    public static function canCreateByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_ups.create') ?? false;
    }

    public static function canEditByPermission($record): bool
    {
        return Auth::user()?->can('crm_follow_ups.update') ?? false;
    }

    public static function canDeleteByPermission($record): bool
    {
        return Auth::user()?->can('crm_follow_ups.delete') ?? false;
    }

    public static function canRestoreByPermission($record): bool
    {
        return Auth::user()?->can('crm_follow_ups.restore') ?? false;
    }

    public static function canForceDeleteByPermission($record): bool
    {
        return Auth::user()?->can('crm_follow_ups.force_delete') ?? false;
    }

    public static function canDeleteAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_ups.delete_bulk') ?? false;
    }

    public static function canRestoreAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_ups.restore_bulk') ?? false;
    }

    public static function canForceDeleteAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_ups.force_delete_bulk') ?? false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'opportunity.title',
            'opportunity.client.name',
            'internal_notes',
            'customer_reply',
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var OpportunityFollowUp $record */
        return [
            __('crm.fields.opportunity') => $record->opportunity?->title,
            __('crm.fields.client') => $record->opportunity?->client?->name,
            __('crm.fields.scheduled_at') => $record->scheduled_at?->format('Y-m-d H:i'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'opportunity.client',
                'opportunity.opportunityStage',
                'followUpType',
                'followUpStatus',
                'assignedTo',
                'parentFollowUp',
                'latestNote',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return OpportunityFollowUpForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OpportunityFollowUpInfolistSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpportunityFollowUpsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OpportunityFollowUpChildrenRelationManager::class,
            OpportunityFollowUpNotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOpportunityFollowUps::route('/'),
            'create' => CreateOpportunityFollowUp::route('/create'),
            'view' => ViewOpportunityFollowUp::route('/{record}'),
            'edit' => EditOpportunityFollowUp::route('/{record}/edit'),
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
