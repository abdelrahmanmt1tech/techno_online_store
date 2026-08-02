<?php

namespace App\Filament\Tenant\Resources\Operations;

use App\Filament\Tenant\Resources\Operations\Pages\CreateOperation;
use App\Filament\Tenant\Resources\Operations\Pages\EditOperation;
use App\Filament\Tenant\Resources\Operations\Pages\ListOperations;
use App\Filament\Tenant\Resources\Operations\Pages\ViewOperation;
use App\Filament\Tenant\Resources\Operations\Schemas\OperationForm;
use App\Filament\Tenant\Resources\Operations\Schemas\OperationInfolist;
use App\Filament\Tenant\Resources\Operations\Tables\OperationsTable;
use App\Models\Tenant\Operation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use App\Enums\OperationType;

class OperationResource extends Resource
{
    protected static ?string $model = Operation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'comment';



    protected static ?string $navigationLabel = null;
    protected static ?string $pluralModelLabel = null;
    protected static ?string $modelLabel = null;


    public static function getNavigationLabel(): string
    {
        return __('dashboard.resources.operation.nav');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.resources.operation.plural_model_label');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.resources.operation.model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('operations.view') ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('operations.view') ?? false;
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('operations.view') ?? false;
    }

    public static function canView($record): bool
    {
        return Auth::user()?->can('operations.show') ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('operations.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        // [ADDED] منع تعديل القيود الافتتاحية من مورد العمليات العام.
        if (($record?->operation_type?->value ?? (string) $record?->operation_type) === OperationType::OPENING->value) {
            return false;
        }

        return Auth::user()?->can('operations.update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->can('operations.delete') ?? false;
    }

    public static function canRestore($record): bool
    {
        return Auth::user()?->can('operations.restore') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return Auth::user()?->can('operations.force_delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->can('operations.delete_bulk') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return Auth::user()?->can('operations.restore_bulk') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return Auth::user()?->can('operations.force_delete_bulk') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return OperationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OperationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OperationsTable::configure($table);
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
            'index' => ListOperations::route('/'),
            'create' => CreateOperation::route('/create'),
            'view' => ViewOperation::route('/{record}'),
            'edit' => EditOperation::route('/{record}/edit'),
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
