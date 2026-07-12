<?php

namespace App\Filament\Tenant\Resources\MessengerApiRequests\Pages;

use App\Filament\Tenant\Resources\MessengerApiRequests\MessengerApiRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListMessengerApiRequests extends ListRecords
{
    protected static string $resource = MessengerApiRequestResource::class;
}
