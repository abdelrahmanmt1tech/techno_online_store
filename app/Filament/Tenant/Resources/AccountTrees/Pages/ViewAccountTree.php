<?php

namespace App\Filament\Tenant\Resources\AccountTrees\Pages;

use App\Filament\Tenant\Resources\AccountTrees\AccountTreeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewAccountTree extends ViewRecord
{
    protected static string $resource = AccountTreeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('account_trees.update') ?? false),
        ];
    }
}
