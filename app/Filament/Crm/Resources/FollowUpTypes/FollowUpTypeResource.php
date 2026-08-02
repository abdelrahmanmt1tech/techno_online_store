<?php

namespace App\Filament\Crm\Resources\FollowUpTypes;

use App\Filament\Crm\CrmResource;
use App\Filament\Crm\Resources\FollowUpTypes\Pages\ManageFollowUpTypes;
use App\Models\Tenant\FollowUpType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class FollowUpTypeResource extends CrmResource
{
    protected static ?string $model = FollowUpType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 31;

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.follow_up_type.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.follow_up_type.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.follow_up_type.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.settings');
    }

    public static function canViewAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_up_types.view') ?? false;
    }

    public static function canCreateByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_up_types.create') ?? false;
    }

    public static function canEditByPermission($record): bool
    {
        return Auth::user()?->can('crm_follow_up_types.update') ?? false;
    }

    public static function canDeleteByPermission($record): bool
    {
        return Auth::user()?->can('crm_follow_up_types.delete') ?? false;
    }

    public static function canRestoreByPermission($record): bool
    {
        return Auth::user()?->can('crm_follow_up_types.restore') ?? false;
    }

    public static function canForceDeleteByPermission($record): bool
    {
        return Auth::user()?->can('crm_follow_up_types.force_delete') ?? false;
    }

    public static function canDeleteAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_up_types.delete_bulk') ?? false;
    }

    public static function canRestoreAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_up_types.restore_bulk') ?? false;
    }

    public static function canForceDeleteAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_follow_up_types.force_delete_bulk') ?? false;
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
                FileUpload::make('icon')
                    ->disk('public')
                    ->directory('icons/follow-up-types')
                    ->image()
                    ->imageEditor()
                    ->maxSize(2048),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('icon'),
                TextColumn::make('name')
                    ->searchable(),
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
            'index' => ManageFollowUpTypes::route('/'),
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
