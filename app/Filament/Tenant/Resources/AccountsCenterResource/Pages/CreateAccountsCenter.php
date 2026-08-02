<?php

namespace App\Filament\Tenant\Resources\AccountsCenterResource\Pages;

use App\Filament\Tenant\Resources\AccountsCenterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccountsCenter extends CreateRecord
{
    protected static string $resource = AccountsCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
