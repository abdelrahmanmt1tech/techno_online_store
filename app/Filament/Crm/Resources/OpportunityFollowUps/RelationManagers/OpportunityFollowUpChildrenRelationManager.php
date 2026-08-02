<?php

namespace App\Filament\Crm\Resources\OpportunityFollowUps\RelationManagers;

use App\Filament\Crm\Resources\OpportunityFollowUps\OpportunityFollowUpResource;
use App\Filament\SharedForms\NotesTable;
use App\Models\Tenant\OpportunityFollowUp;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OpportunityFollowUpChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'childFollowUps';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('crm.fields.child_follow_ups');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->url(fn (OpportunityFollowUp $record): string => OpportunityFollowUpResource::getUrl('view', ['record' => $record], panel: 'crm')),
                TextColumn::make('followUpType.name')
                    ->label(__('crm.fields.follow_up_type')),
                TextColumn::make('scheduling_state')
                    ->label(__('crm.fields.scheduling_state'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("crm.scheduling.{$state}")),
                TextColumn::make('assignedTo.name')
                    ->label(__('crm.fields.assigned_to'))
                    ->placeholder('-'),
                TextColumn::make('scheduled_at')
                    ->label(__('crm.fields.scheduled_at'))
                    ->dateTime(),
                NotesTable::latestNoteColumn(),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->url(fn (OpportunityFollowUp $record): string => OpportunityFollowUpResource::getUrl('view', ['record' => $record], panel: 'crm')),
            ]);
    }
}
