<?php

namespace App\Filament\Crm\Pages;

use App\Enums\Crm\ClientStage;
use App\Filament\Crm\CrmPage;
use App\Filament\SharedForms\ClientCrmActions;
use App\Filament\Tenant\Resources\Clients\ClientResource;
use App\Models\Tenant\Client;
use App\Models\Tenant\LeadSource;
use App\Models\TenantUser;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class LeadClients extends CrmPage implements HasTable
{
    use InteractsWithTable;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.crm.pages.lead-clients';

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.lead_clients.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.pipeline');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Client::query()
                    ->where('stage', ClientStage::LEAD)
                    ->with(['leadSource', 'salesRep', 'firstFollower', 'latestOpportunity'])
                    ->withCount([
                        'opportunities as open_opportunities_count' => fn (Builder $query): Builder => $query->open(),
                    ])
                    ->orderByDesc('id')
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('crm.fields.client'))
                    ->formatStateUsing(fn ($state): string => $this->translatableLabel($state))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(__('crm.fields.phone'))
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('crm.fields.email'))
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('leadSource.name')
                    ->label(__('crm.fields.source'))
                    ->formatStateUsing(fn ($state): string => $this->translatableLabel($state, '-'))
                    ->placeholder('-'),
                TextColumn::make('salesRep.name')
                    ->label(__('crm.fields.assigned_to'))
                    ->placeholder('-'),
                TextColumn::make('firstFollower.name')
                    ->label(__('crm.fields.first_assigned_to'))
                    ->placeholder('-'),
                TextColumn::make('open_opportunities_count')
                    ->label(__('crm.fields.open_opportunities_count'))
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->url(fn (Client $record): string => ClientCrmActions::openOpportunitiesUrl($record)),
                TextColumn::make('latestOpportunity.title')
                    ->label(__('crm.fields.latest_opportunity'))
                    ->placeholder('-')
                    ->limit(30)
                    ->url(fn (Client $record): ?string => $record->latestOpportunity
                        ? ClientCrmActions::opportunityViewUrl($record->latestOpportunity)
                        : null),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('lead_source_id')
                    ->label(__('crm.fields.source'))
                    ->options(fn (): array => LeadSource::query()
                        ->orderBy('id')
                        ->get()
                        ->mapWithKeys(fn (LeadSource $source): array => [$source->id => $this->translatableLabel($source->name, (string) $source->id)])
                        ->all()),
                SelectFilter::make('sales_rep_id')
                    ->label(__('crm.fields.assigned_to'))
                    ->options(fn (): array => TenantUser::query()->orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->headerActions([
                Action::make('addLead')
                    ->label(__('crm.channels.lead_clients.add'))
                    ->icon(Heroicon::UserPlus)
                    ->color('success')
                    ->modalHeading(__('crm.channels.lead_clients.add_heading'))
                    ->modalSubmitActionLabel(__('crm.channels.lead_clients.save'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('crm.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label(__('crm.fields.phone'))
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->label(__('crm.fields.email'))
                            ->email()
                            ->maxLength(255),
                        Select::make('lead_source_id')
                            ->label(__('crm.fields.source'))
                            ->options(fn (): array => LeadSource::query()
                                ->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn (LeadSource $source): array => [$source->id => $this->translatableLabel($source->name, (string) $source->id)])
                                ->all())
                            ->searchable()
                            ->preload(),
                        Select::make('sales_rep_id')
                            ->label(__('crm.fields.assigned_to'))
                            ->options(fn (): array => TenantUser::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->default(fn (): ?int => Auth::id())
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (array $data): void {
                        $salesRepId = (int) ($data['sales_rep_id'] ?? Auth::id());

                        Client::create([
                            'name' => $data['name'],
                            'phone' => $data['phone'] ?: null,
                            'email' => $data['email'] ?: null,
                            'lead_source_id' => $data['lead_source_id'] ?: null,
                            'sales_rep_id' => $salesRepId ?: null,
                            'first_followed_by' => $salesRepId ?: null,
                            'stage' => ClientStage::LEAD,
                        ]);

                        Notification::make()
                            ->title(__('crm.channels.lead_clients.added_title'))
                            ->success()
                            ->send();
                    }),
            ])
            ->recordUrl(fn (Client $record): string => ClientResource::getUrl('view', ['record' => $record], panel: 'crm'))
            ->recordActions([
                ClientCrmActions::viewWorkspaceAction(),
                Action::make('editLead')
                    ->label(__('crm.actions.edit_client'))
                    ->icon(Heroicon::PencilSquare)
                    ->url(fn (Client $record): string => ClientResource::getUrl('edit', ['record' => $record], panel: 'crm')),
                Action::make('openOpportunities')
                    ->label(__('crm.actions.view_open_opportunities'))
                    ->icon(Heroicon::Briefcase)
                    ->url(fn (Client $record): string => ClientCrmActions::openOpportunitiesUrl($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    BulkAction::make('stageChange')
                        ->label(__('crm.channels.lead_clients.stage_change'))
                        ->icon(Heroicon::ArrowPath)
                        ->color('warning')
                        ->schema([
                            Select::make('stage')
                                ->label(__('crm.fields.stage'))
                                ->options(ClientStage::options())
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update([
                                    'stage' => $data['stage'],
                                ]);
                            }

                            Notification::make()
                                ->title(__('crm.channels.lead_clients.stage_changed_title'))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    protected function translatableLabel(mixed $value, string $fallback = ''): string
    {
        if (is_array($value)) {
            return (string) ($value[app()->getLocale()] ?? reset($value) ?: $fallback);
        }

        return (string) ($value ?? $fallback);
    }
}
