<?php

namespace App\Filament\Crm\Schemas;

use App\Enums\Crm\ClientStage;
use App\Filament\SharedForms\ClientCrmActions;
use App\Models\Tenant\Client;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientCrmWorkspaceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('crm.sections.client_details'))
                    ->columns(4)
                    ->columnSpanFull()
                    ->footerActions(ClientCrmActions::clientPageActions())
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('dashboard.fields.name')),
                        TextEntry::make('stage')
                            ->label(__('crm.fields.stage'))
                            ->formatStateUsing(fn ($state) => $state instanceof ClientStage ? $state->label() : ClientStage::tryFrom((string) $state)?->label() ?? $state)
                            ->badge()
                            ->color(fn ($state) => $state instanceof ClientStage ? $state->color() : ClientStage::tryFrom((string) $state)?->color() ?? 'gray'),
                        TextEntry::make('leadSource.name')
                            ->label(__('dashboard.fields.lead_source'))
                            ->placeholder('-'),
                        TextEntry::make('salesRep.name')
                            ->label(__('dashboard.fields.sales_rep'))
                            ->placeholder('-'),
                        TextEntry::make('firstFollower.name')
                            ->label(__('crm.fields.first_assigned_to'))
                            ->placeholder('-'),
                        TextEntry::make('contactInfos.0.phone')
                            ->label(__('dashboard.fields.phone'))
                            ->placeholder('-'),
                        TextEntry::make('contactInfos.0.email')
                            ->label(__('dashboard.fields.email'))
                            ->placeholder('-'),
                        TextEntry::make('contactInfos.0.whatsapp')
                            ->label(__('dashboard.fields.whatsapp'))
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label(__('crm.fields.created_at'))
                            ->dateTime(),
                    ]),

                Section::make(__('crm.sections.opportunities'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('open_opportunities_count')
                            ->label(__('crm.fields.open_opportunities_count'))
                            ->badge()
                            ->color('success')
                            ->state(fn (Client $record): int => (int) ($record->open_opportunities_count ?? $record->openOpportunities()->count()))
                            ->url(fn (Client $record): string => ClientCrmActions::openOpportunitiesUrl($record)),
                        TextEntry::make('latestOpportunity.title')
                            ->label(__('crm.fields.latest_opportunity'))
                            ->placeholder('-')
                            ->url(fn (Client $record): ?string => $record->latestOpportunity
                                ? ClientCrmActions::opportunityViewUrl($record->latestOpportunity)
                                : null)
                            ->color('primary'),
                        TextEntry::make('latestOpportunity.opportunityStage.name')
                            ->label(__('crm.fields.latest_opportunity_stage'))
                            ->badge()
                            ->color(fn (Client $record): string => $record->latestOpportunity?->opportunityStage?->color ?? 'gray')
                            ->placeholder('-'),
                    ]),

                Section::make(__('crm.sections.follow_ups'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('last_completed_follow_up')
                            ->label(__('crm.fields.last_completed_follow_up'))
                            ->state(fn (Client $record): string => ClientCrmActions::formatFollowUpSummary($record->resolveLastCompletedFollowUp()))
                            ->placeholder('-'),
                        TextEntry::make('next_scheduled_follow_up')
                            ->label(__('crm.fields.next_scheduled_follow_up'))
                            ->state(fn (Client $record): string => ClientCrmActions::formatFollowUpSummary($record->resolveNextScheduledFollowUp()))
                            ->placeholder('-')
                            ->color(fn (Client $record): ?string => match ($record->resolveNextScheduledFollowUp()?->scheduling_state) {
                                'overdue' => 'danger',
                                'scheduled' => 'warning',
                                default => null,
                            }),
                    ]),
            ]);
    }
}
