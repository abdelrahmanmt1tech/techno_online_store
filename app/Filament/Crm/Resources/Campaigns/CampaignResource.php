<?php

namespace App\Filament\Crm\Resources\Campaigns;

use App\Filament\Crm\CrmResource;
use App\Filament\Crm\Resources\Campaigns\Pages\ManageCampaigns;
use App\Models\Tenant\Campaign;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class CampaignResource extends CrmResource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.campaign.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.campaign.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.campaign.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.pipeline');
    }

    public static function canViewAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_campaigns.view') ?? false;
    }

    public static function canCreateByPermission(): bool
    {
        return Auth::user()?->can('crm_campaigns.create') ?? false;
    }

    public static function canEditByPermission($record): bool
    {
        return Auth::user()?->can('crm_campaigns.update') ?? false;
    }

    public static function canDeleteByPermission($record): bool
    {
        return Auth::user()?->can('crm_campaigns.delete') ?? false;
    }

    public static function canRestoreByPermission($record): bool
    {
        return Auth::user()?->can('crm_campaigns.restore') ?? false;
    }

    public static function canForceDeleteByPermission($record): bool
    {
        return Auth::user()?->can('crm_campaigns.force_delete') ?? false;
    }

    public static function canDeleteAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_campaigns.delete_bulk') ?? false;
    }

    public static function canRestoreAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_campaigns.restore_bulk') ?? false;
    }

    public static function canForceDeleteAnyByPermission(): bool
    {
        return Auth::user()?->can('crm_campaigns.force_delete_bulk') ?? false;
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
                Textarea::make('description.ar')
                    ->label(__('dashboard.fields.description').' (ar)'),
                Textarea::make('description.en')
                    ->label(__('dashboard.fields.description').' (en)'),
                TextInput::make('budget')
                    ->label(__('crm.fields.budget'))
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                DatePicker::make('start_date')
                    ->label(__('crm.fields.start_date')),
                DatePicker::make('end_date')
                    ->label(__('crm.fields.end_date')),
                Select::make('status')
                    ->label(__('crm.fields.status'))
                    ->options([
                        'draft' => __('crm.campaign_status_options.draft'),
                        'active' => __('crm.campaign_status_options.active'),
                        'paused' => __('crm.campaign_status_options.paused'),
                        'completed' => __('crm.campaign_status_options.completed'),
                    ])
                    ->default('draft')
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
                TextColumn::make('budget')
                    ->label(__('crm.fields.budget'))
                    ->money('SAR'),
                TextColumn::make('status')
                    ->label(__('crm.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('crm.campaign_status_options.'.$state)),
                TextColumn::make('start_date')
                    ->label(__('crm.fields.start_date'))
                    ->date(),
                TextColumn::make('end_date')
                    ->label(__('crm.fields.end_date'))
                    ->date(),
                TextColumn::make('opportunities_count')
                    ->counts('opportunities')
                    ->label(__('crm.resources.opportunity.plural')),
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
            'index' => ManageCampaigns::route('/'),
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
