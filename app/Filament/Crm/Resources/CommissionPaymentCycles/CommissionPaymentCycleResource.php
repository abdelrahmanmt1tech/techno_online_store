<?php

namespace App\Filament\Crm\Resources\CommissionPaymentCycles;

use App\Filament\Crm\CrmResource;
use App\Filament\Crm\Resources\CommissionPaymentCycles\Pages\CreateCommissionPaymentCycle;
use App\Filament\Crm\Resources\CommissionPaymentCycles\Pages\EditCommissionPaymentCycle;
use App\Filament\Crm\Resources\CommissionPaymentCycles\Pages\ListCommissionPaymentCycles;
use App\Filament\Crm\Resources\CommissionPaymentCycles\Pages\ViewCommissionPaymentCycle;
use App\Filament\Crm\Resources\CommissionPaymentCycles\RelationManagers\CommissionPaymentsRelationManager;
use App\Filament\Crm\Resources\CommissionPaymentCycles\Schemas\CommissionPaymentCycleForm;
use App\Filament\Crm\Resources\CommissionPaymentCycles\Schemas\CommissionPaymentCycleInfolist;
use App\Filament\Crm\Resources\CommissionPaymentCycles\Tables\CommissionPaymentCyclesTable;
use App\Models\Tenant\CommissionPaymentCycle;
use App\Models\TenantUser;
use App\Support\Crm\Commission\CommissionPaymentCycleAccess;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class CommissionPaymentCycleResource extends CrmResource
{
    protected static ?string $model = CommissionPaymentCycle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    protected static ?string $recordTitleAttribute = 'cycle_number';

    protected static ?int $navigationSort = 25;

    public static function getNavigationLabel(): string
    {
        return __('crm.payment_cycles.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.payment_cycles.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.payment_cycles.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.commissions');
    }

    public static function canViewAnyByPermission(): bool
    {
        $user = Auth::user();

        return $user instanceof TenantUser
            && CommissionPaymentCycleAccess::canViewAny($user);
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof TenantUser
            && $record instanceof CommissionPaymentCycle
            && CommissionPaymentCycleAccess::canView($user, $record);
    }

    public static function canCreateByPermission(): bool
    {
        $user = Auth::user();

        return $user instanceof TenantUser
            && CommissionPaymentCycleAccess::canCreate($user);
    }

    public static function canEditByPermission($record): bool
    {
        $user = Auth::user();

        return $user instanceof TenantUser
            && $record instanceof CommissionPaymentCycle
            && CommissionPaymentCycleAccess::canUpdate($user, $record);
    }

    public static function canDeleteByPermission($record): bool
    {
        $user = Auth::user();

        return $user instanceof TenantUser
            && $record instanceof CommissionPaymentCycle
            && CommissionPaymentCycleAccess::canDelete($user, $record);
    }

    public static function canRestoreByPermission($record): bool
    {
        $user = Auth::user();

        return $user instanceof TenantUser
            && $record instanceof CommissionPaymentCycle
            && CommissionPaymentCycleAccess::canDelete($user, $record);
    }

    public static function canForceDeleteByPermission($record): bool
    {
        $user = Auth::user();

        return $user instanceof TenantUser
            && $record instanceof CommissionPaymentCycle
            && CommissionPaymentCycleAccess::canDelete($user, $record);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['branch', 'createdBy', 'approvedBy', 'paidBy']);

        $user = Auth::user();

        if ($user instanceof TenantUser) {
            $query->visibleToUser($user);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return CommissionPaymentCycleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommissionPaymentCycleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommissionPaymentCyclesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CommissionPaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommissionPaymentCycles::route('/'),
            'create' => CreateCommissionPaymentCycle::route('/create'),
            'view' => ViewCommissionPaymentCycle::route('/{record}'),
            'edit' => EditCommissionPaymentCycle::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['branch', 'createdBy', 'approvedBy', 'paidBy', 'allocations']);

        $user = Auth::user();

        if ($user instanceof TenantUser) {
            $query->visibleToUser($user);
        }

        return $query;
    }
}
