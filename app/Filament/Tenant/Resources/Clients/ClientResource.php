<?php

namespace App\Filament\Tenant\Resources\Clients;

use App\Filament\Tenant\Resources\Clients\RelationManagers\ClientNotesRelationManager;
use App\Filament\Tenant\Resources\Clients\RelationManagers\ClientOpportunitiesRelationManager;
use App\Filament\Tenant\Resources\Clients\Pages\CreateClient;
use App\Filament\Tenant\Resources\Clients\Pages\EditClient;
use App\Filament\Tenant\Resources\Clients\Pages\ListClients;
use App\Filament\Tenant\Resources\Clients\Pages\ViewClient;
use App\Filament\Tenant\Resources\Clients\Schemas\ClientForm;
use App\Filament\Tenant\Resources\Clients\Schemas\ClientInfolist;
use App\Filament\Tenant\Resources\Clients\Tables\ClientsTable;
use App\Models\Tenant\Client;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

//    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';




    protected static ?string $slug = 'clients';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = null;
    protected static ?string $pluralModelLabel = null;
    protected static ?string $modelLabel = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.sidebar.clients');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.resources.client.plural_model_label');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.resources.client.model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.resources.client.nav_group');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('clients.view') ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('clients.view') ?? false;
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('clients.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('clients.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->can('clients.update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->can('clients.delete') ?? false;
    }

    public static function canRestore($record): bool
    {
        return Auth::user()?->can('clients.restore') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return Auth::user()?->can('clients.force_delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->can('clients.delete_bulk') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return Auth::user()?->can('clients.restore_bulk') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return Auth::user()?->can('clients.force_delete_bulk') ?? false;
    }















    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['latestNote', 'salesRep', 'leadSource', 'firstFollower', 'contactInfos', 'latestOpportunity.opportunityStage']);
    }

    public static function form(Schema $schema): Schema
    {
        return ClientForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClientInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        if (filament()->getCurrentPanel()?->getId() !== 'crm') {
            return [];
        }

        return [
            ClientOpportunitiesRelationManager::class,
            ClientNotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'view' => ViewClient::route('/{record}'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
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
        return ['name', 'salesRep.name'];
    }


}
