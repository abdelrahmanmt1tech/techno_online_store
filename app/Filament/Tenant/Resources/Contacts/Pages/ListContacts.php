<?php

namespace App\Filament\Tenant\Resources\Contacts\Pages;

use App\Filament\Tenant\Resources\Contacts\ContactResource;
use Filament\Resources\Pages\ListRecords;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
