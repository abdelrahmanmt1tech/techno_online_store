<?php

namespace App\Filament\Tenant\Resources\Clients\RelationManagers;

use App\Filament\SharedForms\ClientCrmActions;
use App\Models\Tenant\Opportunity;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientOpportunitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'opportunities';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('crm.resources.opportunity.plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label(__('crm.fields.title')),
                TextColumn::make('opportunityStage.name')->label(__('crm.fields.stage')),
                TextColumn::make('amount')->label(__('crm.fields.amount'))->money('SAR'),
                TextColumn::make('assignedTo.name')->label(__('crm.fields.assigned_to')),
                IconColumn::make('is_closed')->label(__('crm.fields.is_closed'))->boolean(),
                TextColumn::make('created_at')->label(__('crm.fields.created_at'))->dateTime(),
            ])
            ->headerActions([
                Action::make('view_open')
                    ->label(__('crm.actions.view_open_opportunities'))
                    ->url(fn (): string => ClientCrmActions::openOpportunitiesUrl($this->getOwnerRecord())),
            ])
            ->recordActions([
                Action::make('view')
                    ->url(fn (Opportunity $record): string => ClientCrmActions::opportunityViewUrl($record)),
            ]);
    }
}
