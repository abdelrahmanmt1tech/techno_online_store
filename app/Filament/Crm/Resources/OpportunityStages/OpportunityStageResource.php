<?php

namespace App\Filament\Crm\Resources\OpportunityStages;

use App\Enums\Crm\OpportunityStageAction;
use App\Filament\Crm\CrmResource;
use App\Filament\Crm\Resources\OpportunityStages\Pages\ManageOpportunityStages;
use App\Models\Tenant\OpportunityStage;
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
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class OpportunityStageResource extends CrmResource
{
    protected static ?string $model = OpportunityStage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.opportunity_stage.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.opportunity_stage.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.opportunity_stage.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.settings');
    }

    public static function canViewAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_opportunity_stages.view') ?? false;
    }

    public static function canCreateByPermission(): bool
    {
        return Auth::user()?->can('crm_opportunity_stages.create') ?? false;
    }

    public static function canEditByPermission($record): bool
    {
        return Auth::user()?->can('crm_opportunity_stages.update') ?? false;
    }

    public static function canDeleteByPermission($record): bool
    {
        return Auth::user()?->can('crm_opportunity_stages.delete') ?? false;
    }

    public static function canRestoreByPermission($record): bool
    {
        return Auth::user()?->can('crm_opportunity_stages.restore') ?? false;
    }

    public static function canForceDeleteByPermission($record): bool
    {
        return Auth::user()?->can('crm_opportunity_stages.force_delete') ?? false;
    }

    public static function canDeleteAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_opportunity_stages.delete_bulk') ?? false;
    }

    public static function canRestoreAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_opportunity_stages.restore_bulk') ?? false;
    }

    public static function canForceDeleteAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_opportunity_stages.force_delete_bulk') ?? false;
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
                    ->options(OpportunityStageAction::options())
                    ->required(),
                Toggle::make('is_final')
                    ->label(__('crm.fields.is_final')),
                TextInput::make('sort_order')
                    ->label(__('crm.fields.sort_order'))
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                ColorColumn::make('color'),
                TextColumn::make('action')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof OpportunityStageAction ? $state->label() : OpportunityStageAction::tryFrom((string) $state)?->label() ?? '-')
                    ->color(fn ($state) => $state instanceof OpportunityStageAction ? $state->color() : OpportunityStageAction::tryFrom((string) $state)?->color() ?? 'gray'),
                IconColumn::make('is_final')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->sortable(),
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
            'index' => ManageOpportunityStages::route('/'),
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
