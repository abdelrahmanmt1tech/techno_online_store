<?php

namespace App\Filament\Tenant\Resources\Governorates\Pages;

use App\Filament\Tenant\Resources\Governorates\GovernorateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListGovernorates extends ListRecords
{
    protected static string $resource = GovernorateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => Auth::user()->can('governorates.create')),
        ];
    }
}
