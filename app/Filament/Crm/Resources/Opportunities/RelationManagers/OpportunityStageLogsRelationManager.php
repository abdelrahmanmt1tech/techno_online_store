<?php

namespace App\Filament\Crm\Resources\Opportunities\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OpportunityStageLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'opportunityStageLogs';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('crm.stage_logs.plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fromStage.name')
                    ->label(__('crm.fields.from_stage'))
                    ->badge()
                    ->color(fn ($record) => $record->fromStage?->color ?? 'gray'),
                TextColumn::make('toStage.name')
                    ->label(__('crm.fields.to_stage'))
                    ->badge()
                    ->color(fn ($record) => $record->toStage?->color ?? 'gray'),
                TextColumn::make('changedBy.name')
                    ->label(__('crm.fields.changed_by')),
                TextColumn::make('notes')
                    ->label(__('crm.fields.notes'))
                    ->limit(50)
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
