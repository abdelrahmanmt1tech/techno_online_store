<?php

namespace App\Filament\Crm\Resources\Opportunities\RelationManagers;

use App\Filament\Crm\Resources\OpportunityFollowUps\OpportunityFollowUpResource;
use App\Filament\Crm\Schemas\OpportunityFollowUpFormSchema;
use App\Filament\Crm\Schemas\OpportunityFollowUpInfolist;
use App\Filament\SharedForms\NotesActions;
use App\Filament\SharedForms\NotesTable;
use App\Models\Tenant\OpportunityFollowUp;
use App\Services\Crm\PersistOpportunityFollowUpService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class OpportunityFollowUpsRelationManager extends RelationManager
{
    protected static string $relationship = 'opportunityFollowUps';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('crm.follow_ups.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(OpportunityFollowUpFormSchema::configure(includeOpportunity: false));
    }

    public function infolist(Schema $schema): Schema
    {
        return OpportunityFollowUpInfolist::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('followUpType.name')
                    ->label(__('crm.fields.follow_up_type')),
                TextColumn::make('followUpStatus.name')
                    ->label(__('crm.fields.follow_up_status'))
                    ->badge()
                    ->color(fn ($record) => $record->followUpStatus?->color ?? 'gray'),
                TextColumn::make('scheduling_state')
                    ->label(__('crm.fields.scheduling_state'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("crm.scheduling.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'overdue' => 'danger',
                        default => 'info',
                    }),
                TextColumn::make('assignedTo.name')
                    ->label(__('crm.fields.assigned_to')),
                TextColumn::make('parent_follow_up_id')
                    ->label(__('crm.fields.parent_follow_up'))
                    ->formatStateUsing(fn ($state): string => $state ? '#' . $state : '-')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('scheduled_at')
                    ->label(__('crm.fields.scheduled_at'))
                    ->date()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label(__('crm.fields.completed_at'))
                    ->date()
                    ->placeholder('-'),
                NotesTable::latestNoteColumn(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = Auth::id();

                        return $data;
                    })
                    ->using(fn (array $data): OpportunityFollowUp => $this->persistFollowUp(new OpportunityFollowUp(), $data)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (OpportunityFollowUp $record): string => OpportunityFollowUpResource::getUrl('view', ['record' => $record], panel: 'crm')),
                EditAction::make()
                    ->using(fn (OpportunityFollowUp $record, array $data): OpportunityFollowUp => $this->persistFollowUp($record, $data)),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
                NotesActions::addNoteAction(),
                NotesActions::viewNotesAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }

    protected function persistFollowUp(OpportunityFollowUp $followUp, array $data): OpportunityFollowUp
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        return app(PersistOpportunityFollowUpService::class)->handle(
            $followUp,
            $data,
            $user,
            $this->getOwnerRecord()->getKey(),
        );
    }
}
