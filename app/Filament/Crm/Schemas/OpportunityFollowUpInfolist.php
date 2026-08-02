<?php

namespace App\Filament\Crm\Schemas;

use App\Filament\Tenant\Resources\Clients\ClientResource;
use App\Filament\Crm\Resources\Opportunities\OpportunityResource;
use App\Filament\Crm\Resources\OpportunityFollowUps\OpportunityFollowUpResource;
use App\Filament\SharedForms\NotesFormSchema;
use App\Models\Tenant\OpportunityFollowUp;
use App\Services\Crm\CreateNoteService;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class OpportunityFollowUpInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('crm.sections.relations'))
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('opportunity.title')
                            ->label(__('crm.fields.opportunity'))
                            ->url(fn (OpportunityFollowUp $record): string => OpportunityResource::getUrl('view', ['record' => $record->opportunity_id], panel: 'crm'))
                            ->color('primary'),
                        TextEntry::make('opportunity.client.name')
                            ->label(__('crm.fields.client'))
                            ->url(fn (OpportunityFollowUp $record): ?string => $record->opportunity?->client
                                ? ClientResource::getUrl('view', ['record' => $record->opportunity->client_id])
                                : null)
                            ->color('primary')
                            ->placeholder('-'),
                        TextEntry::make('parentFollowUp.id')
                            ->label(__('crm.fields.parent_follow_up'))
                            ->formatStateUsing(fn ($state, OpportunityFollowUp $record): string => $record->parentFollowUp
                                ? '#' . $record->parentFollowUp->id . ' · ' . ($record->parentFollowUp->scheduled_at?->format('Y-m-d') ?? '-')
                                : '-')
                            ->url(fn (OpportunityFollowUp $record): ?string => $record->parent_follow_up_id
                                ? OpportunityFollowUpResource::getUrl('view', ['record' => $record->parent_follow_up_id], panel: 'crm')
                                : null)
                            ->placeholder('-'),
                        TextEntry::make('childFollowUps_count')
                            ->label(__('crm.fields.child_follow_ups'))
                            ->state(fn (OpportunityFollowUp $record): int => $record->childFollowUps()->count())
                            ->badge()
                            ->color('info'),
                    ]),

                Section::make(__('crm.sections.details'))
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('followUpType.name')
                            ->label(__('crm.fields.follow_up_type')),
                        TextEntry::make('followUpStatus.name')
                            ->label(__('crm.fields.follow_up_status'))
                            ->badge()
                            ->color(fn (OpportunityFollowUp $record): string => $record->followUpStatus?->color ?? 'gray'),
                        TextEntry::make('scheduling_state')
                            ->label(__('crm.fields.scheduling_state'))
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => __("crm.scheduling.{$state}"))
                            ->color(fn (string $state): string => match ($state) {
                                'completed' => 'success',
                                'overdue' => 'danger',
                                default => 'info',
                            }),
                        TextEntry::make('assignedTo.name')
                            ->label(__('crm.fields.assigned_to'))
                            ->placeholder('-'),
                        TextEntry::make('createdBy.name')
                            ->label(__('crm.fields.created_by'))
                            ->placeholder('-'),
                        TextEntry::make('scheduled_at')
                            ->label(__('crm.fields.scheduled_at'))
                            ->dateTime(),
                        TextEntry::make('completed_at')
                            ->label(__('crm.fields.completed_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('offer_text')
                            ->label(__('crm.fields.offer_text'))
                            ->columnSpanFull()
                            ->placeholder('-'),
                        TextEntry::make('customer_reply')
                            ->label(__('crm.fields.customer_reply'))
                            ->columnSpanFull()
                            ->placeholder('-'),
                        TextEntry::make('internal_notes')
                            ->label(__('crm.fields.internal_notes'))
                            ->columnSpanFull()
                            ->placeholder('-'),
                        KeyValueEntry::make('meta')
                            ->label(__('crm.fields.meta'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make(__('crm.notes.plural'))
                    ->columnSpanFull()
                    ->columns(4)
                    ->headerActions([
                        Action::make('createComment')
                            ->label(__('crm.notes.add'))
                            ->schema(NotesFormSchema::make())
                            ->action(function (array $data, OpportunityFollowUp $record, CreateNoteService $createNoteService): void {
                                $user = Auth::user();

                                if (! $user) {
                                    return;
                                }

                                $createNoteService->handle(
                                    $record,
                                    $data['note'],
                                    (bool) ($data['is_private'] ?? false),
                                    $user,
                                );

                                $record->refresh();

                                Notification::make()
                                    ->title(__('crm.notes.created'))
                                    ->success()
                                    ->send();
                            }),
                    ])
                    ->schema([
                        RepeatableEntry::make('notes')
                            ->label(__('crm.notes.plural'))
                            ->columns(3)
                            ->table([
                                TableColumn::make(__('crm.fields.created_by')),
                                TableColumn::make(__('crm.fields.note')),
                                TableColumn::make(__('crm.fields.created_at')),
                            ])
                            ->schema([
                                TextEntry::make('createdBy.name')->placeholder('-'),
                                TextEntry::make('note')->placeholder('-'),
                                TextEntry::make('created_at')->dateTime('Y-m-d H:i'),
                            ])
                            ->emptyTooltip(__('crm.notes.empty'))
                            ->grid(2)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }
}
