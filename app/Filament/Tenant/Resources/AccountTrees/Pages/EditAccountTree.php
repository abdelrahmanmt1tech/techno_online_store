<?php

namespace App\Filament\Tenant\Resources\AccountTrees\Pages;

use App\Filament\Tenant\Resources\AccountTrees\AccountTreeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditAccountTree extends EditRecord
{
    protected static string $resource = AccountTreeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('account_trees.show') ?? false),
            DeleteAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('account_trees.delete') ?? false),
            ForceDeleteAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('account_trees.force_delete') ?? false),
            RestoreAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('account_trees.restore') ?? false),
        ];
    }
}
