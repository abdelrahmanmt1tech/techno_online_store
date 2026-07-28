<?php

namespace App\Filament\Tenant\Resources\PosRegisters\Pages;

use App\Filament\Tenant\Resources\PosRegisters\PosRegisterResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePosRegister extends CreateRecord
{
    protected static string $resource = PosRegisterResource::class;
}
