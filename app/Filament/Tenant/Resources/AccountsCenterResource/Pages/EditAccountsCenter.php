<?php

namespace App\Filament\Tenant\Resources\AccountsCenterResource\Pages;

use App\Filament\Tenant\Resources\AccountsCenterResource;
use App\Models\Tenant\AccountsCenter;
use App\Models\Tenant\Entry;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditAccountsCenter extends EditRecord
{
    protected static string $resource = AccountsCenterResource::class;

    protected bool $shouldMoveEntriesToNewTree = true;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('accounts_centers.delete') ?? false),
            ForceDeleteAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('accounts_centers.force_delete') ?? false),
            RestoreAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('accounts_centers.restore') ?? false),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->shouldMoveEntriesToNewTree = (bool) ($data['move_entries_to_new_tree'] ?? true);
        unset($data['move_entries_to_new_tree']);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var AccountsCenter $record */
        $oldTreeId = (int) ($record->account_tree_id ?? 0);
        $newTreeId = (int) ($data['account_tree_id'] ?? 0);

        return DB::transaction(function () use ($record, $data, $oldTreeId, $newTreeId): Model {
            $record->update($data);

            if (
                $this->shouldMoveEntriesToNewTree
                && $oldTreeId > 0
                && $newTreeId > 0
                && $oldTreeId !== $newTreeId
            ) {
                Entry::query()
                    ->where('account_tree_id', $oldTreeId)
                    ->update(['account_tree_id' => $newTreeId]);
            }

            return $record->refresh();
        });
    }
}
