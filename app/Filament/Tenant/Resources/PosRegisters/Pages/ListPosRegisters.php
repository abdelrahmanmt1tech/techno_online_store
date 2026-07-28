<?php

namespace App\Filament\Tenant\Resources\PosRegisters\Pages;

use App\Filament\Tenant\Resources\PosRegisters\PosRegisterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPosRegisters extends ListRecords
{
    protected static string $resource = PosRegisterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
