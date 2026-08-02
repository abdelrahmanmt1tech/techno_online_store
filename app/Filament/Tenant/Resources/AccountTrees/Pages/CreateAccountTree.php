<?php

namespace App\Filament\Tenant\Resources\AccountTrees\Pages;

use App\Filament\Tenant\Resources\AccountTrees\AccountTreeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccountTree extends CreateRecord
{
    protected static string $resource = AccountTreeResource::class;
}
