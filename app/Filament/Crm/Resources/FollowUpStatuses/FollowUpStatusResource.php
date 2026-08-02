<?php

namespace App\Filament\Crm\Resources\FollowUpStatuses;

use App\Enums\Crm\FollowUpStatusAction;
use App\Filament\Crm\CrmResource;
use App\Filament\Crm\Resources\FollowUpStatuses\Pages\ManageFollowUpStatuses;
use App\Models\Tenant\FollowUpStatus;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class FollowUpStatusResource extends CrmResource
{
    protected static ?string $model = FollowUpStatus::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 32;

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.follow_up_status.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.follow_up_status.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.follow_up_status.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.settings');
    }

    public static function canViewAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_up_statuses.view') ?? false;
    }

    public static function canCreateByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_up_statuses.create') ?? false;
    }

    public static function canEditByPermission($record): bool
    {
        return Auth::user()?->can('crm_follow_up_statuses.update') ?? false;
    }

    public static function canDeleteByPermission($record): bool
    {
        return Auth::user()?->can('crm_follow_up_statuses.delete') ?? false;
    }

    public static function canRestoreByPermission($record): bool
    {
        return Auth::user()?->can('crm_follow_up_statuses.restore') ?? false;
    }

    public static function canForceDeleteByPermission($record): bool
    {
        return Auth::user()?->can('crm_follow_up_statuses.force_delete') ?? false;
    }

    public static function canDeleteAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_up_statuses.delete_bulk') ?? false;
    }

    public static function canRestoreAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_up_statuses.restore_bulk') ?? false;
    }

    public static function canForceDeleteAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_up_statuses.force_delete_bulk') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name.ar')
                    ->label(__('dashboard.fields.name_ar'))
                    ->suffix('ar')
                    ->required(),
                TextInput::make('name.en')
                    ->label(__('dashboard.fields.name_en'))
                    ->suffix('en')
                    ->required(),
                ColorPicker::make('color')
                    ->required(),
                Select::make('action')
                    ->label(__('crm.fields.action'))
                    ->options(FollowUpStatusAction::options())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                ColorColumn::make('color'),
                TextColumn::make('action')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof FollowUpStatusAction ? $state->label() : FollowUpStatusAction::tryFrom((string) $state)?->label() ?? '-')
                    ->color(fn ($state) => $state instanceof FollowUpStatusAction ? $state->color() : FollowUpStatusAction::tryFrom((string) $state)?->color() ?? 'gray'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFollowUpStatuses::route('/'),
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
