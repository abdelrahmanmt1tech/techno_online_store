<?php

namespace App\Filament\Crm\Resources\Opportunities\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OpportunityAssignmentLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'opportunityAssignmentLogs';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('crm.assignment_logs.plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fromUser.name')
                    ->label(__('crm.fields.from_user'))
                    ->placeholder('-'),
                TextColumn::make('toUser.name')
                    ->label(__('crm.fields.to_user'))
                    ->placeholder('-'),
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
