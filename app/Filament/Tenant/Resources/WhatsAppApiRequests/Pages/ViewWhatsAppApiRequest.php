<?php

namespace App\Filament\Tenant\Resources\WhatsAppApiRequests\Pages;

use App\Filament\Tenant\Resources\WhatsAppApiRequests\WhatsAppApiRequestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsAppApiRequest extends ViewRecord
{
    protected static string $resource = WhatsAppApiRequestResource::class;

    protected string $view = 'filament.shared.whatsapp.view-api-request';
}
