<?php

namespace App\Filament\Tenant\Resources\WhatsAppWebhookEvents\Pages;

use App\Filament\Tenant\Resources\WhatsAppWebhookEvents\WhatsAppWebhookEventResource;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppWebhookEvents extends ListRecords
{
    protected static string $resource = WhatsAppWebhookEventResource::class;
}
