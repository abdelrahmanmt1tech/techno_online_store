<?php

namespace App\Filament\Tenant\Resources\LeadSources\Pages;

use App\Filament\Tenant\Resources\LeadSources\LeadSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListLeadSources extends ListRecords
{
    protected static string $resource = LeadSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('lead_sources.create') ?? false),
        ];
    }
}
