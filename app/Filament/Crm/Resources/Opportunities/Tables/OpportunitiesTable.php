<?php

namespace App\Filament\Crm\Resources\Opportunities\Tables;

use App\Filament\SharedForms\NotesActions;
use App\Filament\SharedForms\NotesTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Filament\Tables\Columns\Summarizers\Sum;

class OpportunitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('crm.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.name')
                    ->label(__('crm.fields.client'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('opportunityStage.name')
                    ->label(__('crm.fields.stage'))
                    ->badge()
                    ->color(fn ($record) => $record->opportunityStage?->color ?? 'gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('crm.fields.amount'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->summarize(Sum::make()->label(__('crm.summaries.total_amount'))),
                TextColumn::make('assignedTo.name')
                    ->label(__('crm.fields.assigned_to'))
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('campaign.name')
                    ->label(__('crm.fields.campaign'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('branch.name')
                    ->label(__('dashboard.fields.branch'))
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_closed')
                    ->label(__('crm.fields.is_closed'))
                    ->boolean(),
                TextColumn::make('closed_at')
                    ->label(__('crm.fields.closed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                NotesTable::latestNoteColumn(),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('crm.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
        /*    ->defaultGroup(
                Group::make('opportunityStage.name')
                    ->label(__('crm.fields.stage'))
                    ->collapsible(),
            )*/
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('opportunity_stage_id')
                    ->label(__('crm.fields.stage'))
                    ->relationship('opportunityStage', 'name'),
                SelectFilter::make('client_id')
                    ->label(__('crm.fields.client'))
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('campaign_id')
                    ->label(__('crm.fields.campaign'))
                    ->relationship('campaign', 'name'),
                SelectFilter::make('branch_id')
                    ->label(__('dashboard.fields.branch'))
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('assigned_to')
                    ->label(__('crm.fields.assigned_to'))
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_closed')
                    ->label(__('crm.fields.is_closed')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                NotesActions::addNoteAction(),
                NotesActions::viewNotesAction(),
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
