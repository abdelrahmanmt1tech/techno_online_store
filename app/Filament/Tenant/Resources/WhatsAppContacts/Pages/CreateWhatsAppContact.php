<?php

namespace App\Filament\Tenant\Resources\WhatsAppContacts\Pages;

use App\Filament\Tenant\Resources\WhatsAppContacts\WhatsAppContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsAppContact extends CreateRecord
{
    protected static string $resource = WhatsAppContactResource::class;
}
