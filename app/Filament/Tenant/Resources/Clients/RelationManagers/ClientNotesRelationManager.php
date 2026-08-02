<?php

namespace App\Filament\Tenant\Resources\Clients\RelationManagers;

use App\Filament\SharedForms\NotesFormSchema;
use App\Services\Crm\CreateNoteService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ClientNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('crm.notes.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(NotesFormSchema::make());
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                $user = Auth::user();

                if ($user) {
                    $query->visibleTo($user);
                }
            })
            ->columns([
                TextColumn::make('note')
                    ->label(__('crm.fields.note'))
                    ->limit(100)
                    ->searchable(),
                TextColumn::make('createdBy.name')
                    ->label(__('crm.fields.created_by')),
                IconColumn::make('is_private')
                    ->label(__('crm.fields.is_private'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = Auth::id();

                        return $data;
                    })
                    ->using(function (array $data, CreateNoteService $createNoteService): \App\Models\Note {
                        return $createNoteService->handle(
                            $this->getOwnerRecord(),
                            $data['note'],
                            (bool) ($data['is_private'] ?? false),
                            Auth::user(),
                        );
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
