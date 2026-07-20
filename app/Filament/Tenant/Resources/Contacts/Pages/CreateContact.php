<?php

namespace App\Filament\Tenant\Resources\Contacts\Pages;

use App\Filament\Tenant\Resources\Contacts\ContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;
}
