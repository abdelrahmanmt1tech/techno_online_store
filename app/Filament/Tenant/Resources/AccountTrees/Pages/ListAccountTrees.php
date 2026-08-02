<?php

namespace App\Filament\Tenant\Resources\AccountTrees\Pages;

use App\Filament\Tenant\Resources\AccountTrees\AccountTreeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListAccountTrees extends ListRecords
{
    protected static string $resource = AccountTreeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('account_trees.create') ?? false),
        ];
    }
}
