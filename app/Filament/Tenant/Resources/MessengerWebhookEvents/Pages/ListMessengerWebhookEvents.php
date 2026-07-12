<?php

namespace App\Filament\Tenant\Resources\MessengerWebhookEvents\Pages;

use App\Filament\Tenant\Resources\MessengerWebhookEvents\MessengerWebhookEventResource;
use Filament\Resources\Pages\ListRecords;

class ListMessengerWebhookEvents extends ListRecords
{
    protected static string $resource = MessengerWebhookEventResource::class;
}
