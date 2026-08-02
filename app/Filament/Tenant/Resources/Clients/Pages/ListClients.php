<?php

namespace App\Filament\Tenant\Resources\Clients\Pages;

use App\Filament\Tenant\Resources\Clients\ClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('clients.create') ?? false),

        ];
    }
}
