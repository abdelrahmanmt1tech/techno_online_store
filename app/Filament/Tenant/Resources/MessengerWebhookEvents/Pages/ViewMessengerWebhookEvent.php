<?php

namespace App\Filament\Tenant\Resources\MessengerWebhookEvents\Pages;

use App\Filament\Tenant\Resources\MessengerWebhookEvents\MessengerWebhookEventResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMessengerWebhookEvent extends ViewRecord
{
    protected static string $resource = MessengerWebhookEventResource::class;

    protected string $view = 'filament.shared.messenger.view-webhook-event';

    public bool $showRawPayload = true;
}
