<?php

namespace App\Filament\Tenant\Resources\AccountsCenterResource\Pages;

use App\Filament\Tenant\Resources\AccountsCenterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListAccountsCenters extends ListRecords
{
    protected static string $resource = AccountsCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('accounts_centers.create') ?? false),
        ];
    }
}
