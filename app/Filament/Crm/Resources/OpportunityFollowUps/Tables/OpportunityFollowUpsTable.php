<?php

namespace App\Filament\Crm\Resources\OpportunityFollowUps\Tables;

use App\Filament\Crm\Resources\Opportunities\OpportunityResource;
use App\Filament\Crm\Resources\OpportunityFollowUps\OpportunityFollowUpResource;
use App\Filament\Tenant\Resources\Clients\ClientResource;
use App\Filament\SharedForms\NotesActions;
use App\Filament\SharedForms\NotesTable;
use App\Models\Tenant\Client;
use App\Models\Tenant\OpportunityFollowUp;
use App\Models\Tenant\OpportunityStage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OpportunityFollowUpsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_at', 'desc')
            ->recordUrl(fn (OpportunityFollowUp $record): string => OpportunityFollowUpResource::getUrl('view', ['record' => $record], panel: 'crm'))
            ->columns([
                ColumnGroup::make(__('crm.sections.relations'), [
                    TextColumn::make('id')
                        ->label('#')
                        ->sortable()
                        ->toggleable(),
                    TextColumn::make('opportunity.title')
                        ->label(__('crm.fields.opportunity'))
                        ->searchable()
                        ->sortable()
                        ->limit(30)
                        ->url(fn (OpportunityFollowUp $record): string => OpportunityResource::getUrl('view', ['record' => $record->opportunity_id], panel: 'crm'))
                        ->color('primary'),
                    TextColumn::make('opportunity.client.name')
                        ->label(__('crm.fields.client'))
                        ->searchable()
                        ->sortable()
                        ->limit(25)
                        ->url(fn (OpportunityFollowUp $record): ?string => $record->opportunity?->client_id
                            ? ClientResource::getUrl('view', ['record' => $record->opportunity->client_id])
                            : null)
                        ->color('gray')
                        ->placeholder('-'),
                    TextColumn::make('opportunity.opportunityStage.name')
                        ->label(__('crm.fields.stage'))
                        ->badge()
                        ->color(fn (OpportunityFollowUp $record): string => $record->opportunity?->opportunityStage?->color ?? 'gray')
                        ->sortable(),
                ])->alignCenter()->wrapHeader(),
                ColumnGroup::make(__('crm.sections.follow_ups'), [
                    TextColumn::make('followUpType.name')
                        ->label(__('crm.fields.follow_up_type'))
                        ->badge()
                        ->sortable(),
                    TextColumn::make('followUpStatus.name')
                        ->label(__('crm.fields.follow_up_status'))
                        ->badge()
                        ->color(fn (OpportunityFollowUp $record): string => $record->followUpStatus?->color ?? 'gray')
                        ->sortable(),
                    TextColumn::make('scheduling_state')
                        ->label(__('crm.fields.scheduling_state'))
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => __("crm.scheduling.{$state}"))
                        ->color(fn (string $state): string => match ($state) {
                            'completed' => 'success',
                            'overdue' => 'danger',
                            default => 'info',
                        }),
                ])->alignCenter()->wrapHeader(),
                ColumnGroup::make(__('crm.table_groups.assignment'), [
                    TextColumn::make('assignedTo.name')
                        ->label(__('crm.fields.assigned_to'))
                        ->placeholder('-')
                        ->sortable(),
                ])->alignCenter()->wrapHeader(),
                ColumnGroup::make(__('crm.table_groups.scheduling'), [
                    TextColumn::make('scheduled_at')
                        ->label(__('crm.fields.scheduled_at'))
                        ->dateTime()
                        ->sortable(),
                    TextColumn::make('completed_at')
                        ->label(__('crm.fields.completed_at'))
                        ->dateTime()
                        ->placeholder('-')
                        ->sortable(),
                    TextColumn::make('parent_follow_up_id')
                        ->label(__('crm.fields.parent_follow_up'))
                        ->formatStateUsing(fn ($state): string => $state ? '#'.$state : '-')
                        ->url(fn (OpportunityFollowUp $record): ?string => $record->parent_follow_up_id
                            ? OpportunityFollowUpResource::getUrl('view', ['record' => $record->parent_follow_up_id], panel: 'crm')
                            : null)
                        ->placeholder('-')
                        ->toggleable(isToggledHiddenByDefault: true),
                ])->alignCenter()->wrapHeader(),
                ColumnGroup::make(__('crm.table_groups.notes'), [
                    NotesTable::latestNoteColumn(),
                ])->alignCenter()->wrapHeader(),
                ColumnGroup::make(__('crm.table_groups.dates'), [
                    TextColumn::make('created_at')
                        ->label(__('crm.fields.created_at'))
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])->alignCenter()->wrapHeader(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('client_id')
                    ->label(__('crm.fields.client'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Client::query()
                        ->where(function (Builder $query) use ($search): void {
                            $query->where('name->ar', 'like', "%{$search}%")
                                ->orWhere('name->en', 'like', "%{$search}%")
                                ->orWhere('company_name->ar', 'like', "%{$search}%")
                                ->orWhere('company_name->en', 'like', "%{$search}%");
                        })
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Client $client): array => [$client->id => $client->name])
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $q, $value) => $q->whereHas(
                            'opportunity',
                            fn (Builder $oq) => $oq->where('client_id', $value),
                        ),
                    )),
                SelectFilter::make('opportunity_stage_id')
                    ->label(__('crm.fields.stage'))
                    ->searchable()
                    ->options(fn (): array => OpportunityStage::query()
                        ->ordered()
                        ->get()
                        ->mapWithKeys(fn (OpportunityStage $stage): array => [$stage->id => $stage->name])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $q, $value) => $q->whereHas(
                            'opportunity',
                            fn (Builder $oq) => $oq->where('opportunity_stage_id', $value),
                        ),
                    )),
                SelectFilter::make('scheduling_state')
                    ->label(__('crm.fields.scheduling_state'))
                    ->options([
                        'scheduled' => __('crm.scheduling.scheduled'),
                        'overdue' => __('crm.scheduling.overdue'),
                        'completed' => __('crm.scheduling.completed'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'completed' => $query->whereNotNull('completed_at'),
                            'overdue' => $query->overdue(),
                            'scheduled' => $query->whereNull('completed_at')->where('scheduled_at', '>=', now()),
                            default => $query,
                        };
                    }),
                SelectFilter::make('follow_up_type_id')
                    ->label(__('crm.fields.follow_up_type'))
                    ->relationship('followUpType', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('follow_up_status_id')
                    ->label(__('crm.fields.follow_up_status'))
                    ->relationship('followUpStatus', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('assigned_to')
                    ->label(__('crm.fields.assigned_to'))
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('opportunity_id')
                    ->label(__('crm.fields.opportunity'))
                    ->relationship('opportunity', 'title')
                    ->searchable()
                    ->preload(),
                Filter::make('my_follow_ups')
                    ->label(__('crm.filters.my_follow_ups'))
                    ->query(fn (Builder $query): Builder => $query->where('assigned_to', Auth::id()))
                    ->toggle(),
                Filter::make('upcoming_week')
                    ->label(__('crm.filters.upcoming_week'))
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNull('completed_at')
                        ->whereBetween('scheduled_at', [now(), now()->addDays(7)]))
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('view_opportunity')
                    ->label(__('crm.actions.view_opportunity'))
                    ->icon(Heroicon::RectangleStack)
                    ->color('gray')
                    ->url(fn (OpportunityFollowUp $record): string => OpportunityResource::getUrl('view', ['record' => $record->opportunity_id], panel: 'crm')),
                NotesActions::addNoteAction(),
                NotesActions::viewNotesAction(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
