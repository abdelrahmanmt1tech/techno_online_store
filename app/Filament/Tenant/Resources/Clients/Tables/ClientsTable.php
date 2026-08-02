<?php

namespace App\Filament\Tenant\Resources\Clients\Tables;

use App\Enums\Crm\ClientStage;
use App\Filament\SharedForms\ClientCrmActions;
use App\Filament\SharedForms\NotesActions;
use App\Models\Tenant\Client;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('crm.fields.name'))->searchable()->sortable(),
                TextColumn::make('company_name')->label(__('crm.fields.company_name'))->toggleable(),
                TextColumn::make('phone')->label(__('crm.fields.phone'))->toggleable(),
                TextColumn::make('email')->label(__('crm.fields.email'))->toggleable(),
                TextColumn::make('stage')
                    ->label(__('crm.fields.stage'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ClientStage ? $state->value : $state),
                TextColumn::make('salesRep.name')->label(__('crm.fields.sales_rep'))->toggleable(),
                TextColumn::make('leadSource.name')->label(__('crm.fields.lead_source'))->toggleable(),
                TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('stage')->options(
                    collect(ClientStage::cases())->mapWithKeys(fn (ClientStage $s) => [$s->value => $s->name])->all()
                ),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
                NotesActions::addNoteAction(),
                NotesActions::viewNotesAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    BulkAction::make('stageChange')
                        ->label(__('crm.actions.change_stage'))
                        ->schema([
                            Select::make('stage')->options(
                                collect(ClientStage::cases())->mapWithKeys(
                                    fn (ClientStage $s) => [$s->value => $s->name]
                                )->all()
                            )->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            foreach ($records as $record) {
                                /** @var Client $record */
                                $record->stage = $data['stage'];
                                $record->save();
                            }
                            Notification::make()->title(__('crm.actions.updated'))->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
