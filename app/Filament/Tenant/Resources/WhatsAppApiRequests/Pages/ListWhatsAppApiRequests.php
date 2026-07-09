<?php

namespace App\Filament\Tenant\Resources\WhatsAppApiRequests\Pages;

use App\Filament\Tenant\Resources\WhatsAppApiRequests\WhatsAppApiRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppApiRequests extends ListRecords
{
    protected static string $resource = WhatsAppApiRequestResource::class;
}
