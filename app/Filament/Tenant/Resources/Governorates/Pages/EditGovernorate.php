<?php

namespace App\Filament\Tenant\Resources\Governorates\Pages;

use App\Filament\Tenant\Resources\Governorates\GovernorateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditGovernorate extends EditRecord
{
    protected static string $resource = GovernorateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => Auth::user()->can('governorates.delete')),
        ];
    }
}
