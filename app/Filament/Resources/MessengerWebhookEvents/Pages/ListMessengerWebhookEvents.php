<?php

namespace App\Filament\Resources\MessengerWebhookEvents\Pages;

use App\Filament\Resources\MessengerWebhookEvents\MessengerWebhookEventResource;
use Filament\Resources\Pages\ListRecords;

class ListMessengerWebhookEvents extends ListRecords
{
    protected static string $resource = MessengerWebhookEventResource::class;
}
