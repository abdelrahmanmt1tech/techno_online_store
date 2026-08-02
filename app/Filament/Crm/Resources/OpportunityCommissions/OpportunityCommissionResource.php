<?php

namespace App\Filament\Crm\Resources\OpportunityCommissions;

use App\Filament\Crm\CrmResource;
use App\Filament\Crm\Resources\OpportunityCommissions\Pages\CreateOpportunityCommission;
use App\Filament\Crm\Resources\OpportunityCommissions\Pages\EditOpportunityCommission;
use App\Filament\Crm\Resources\OpportunityCommissions\Pages\ListOpportunityCommissions;
use App\Filament\Crm\Resources\OpportunityCommissions\Pages\ViewOpportunityCommission;
use App\Filament\Crm\Resources\OpportunityCommissions\RelationManagers\OpportunityCommissionAdjustmentsRelationManager;
use App\Filament\Crm\Resources\OpportunityCommissions\Schemas\OpportunityCommissionForm;
use App\Filament\Crm\Resources\OpportunityCommissions\Schemas\OpportunityCommissionInfolist;
use App\Filament\Crm\Resources\OpportunityCommissions\Tables\OpportunityCommissionsTable;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Support\Crm\Commission\OpportunityCommissionAccess;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class OpportunityCommissionResource extends CrmResource
{
    protected static ?string $model = OpportunityCommission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('crm.commissions.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.commissions.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.commissions.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.commissions');
    }

    public static function canViewAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_commissions.view_any') ?? false;
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof OpportunityCommission
            && OpportunityCommissionAccess::canView($user, $record);
    }

    public static function canCreateByPermission(): bool
    {
        return Auth::user()?->can('crm_commissions.create') ?? false;
    }

    public static function canEditByPermission($record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof OpportunityCommission
            && OpportunityCommissionAccess::canUpdate($user, $record);
    }

    public static function canDeleteByPermission($record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof OpportunityCommission
            && OpportunityCommissionAccess::canDelete($user, $record);
    }

    public static function canRestoreByPermission($record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof OpportunityCommission
            && OpportunityCommissionAccess::canRestore($user, $record);
    }

    public static function canForceDeleteByPermission($record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof OpportunityCommission
            && OpportunityCommissionAccess::canForceDelete($user, $record);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withFinancialAggregates()
            ->with(['opportunity', 'user', 'branch', 'approvedBy', 'auditLogs.user']);

        $user = Auth::user();

        if ($user instanceof User) {
            $query->visibleToUser($user);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return OpportunityCommissionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OpportunityCommissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpportunityCommissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OpportunityCommissionAdjustmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOpportunityCommissions::route('/'),
            'create' => CreateOpportunityCommission::route('/create'),
            'view' => ViewOpportunityCommission::route('/{record}'),
            'edit' => EditOpportunityCommission::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->withFinancialAggregates();

        $user = Auth::user();

        if ($user instanceof User) {
            $query->visibleToUser($user);
        }

        return $query;
    }
}
