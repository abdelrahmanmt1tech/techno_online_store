<?php

namespace App\Filament\Tenant\Resources\PosRegisters\Pages;

use App\Filament\Tenant\Resources\PosRegisters\PosRegisterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPosRegister extends EditRecord
{
    protected static string $resource = PosRegisterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
