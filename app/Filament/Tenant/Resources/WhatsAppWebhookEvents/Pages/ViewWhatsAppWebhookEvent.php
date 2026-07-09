<?php

namespace App\Filament\Tenant\Resources\WhatsAppWebhookEvents\Pages;

use App\Filament\Shared\WhatsApp\Actions\ReprocessWhatsAppWebhookFilamentAction;
use App\Filament\Tenant\Resources\WhatsAppWebhookEvents\WhatsAppWebhookEventResource;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsAppWebhookEvent extends ViewRecord
{
    protected static string $resource = WhatsAppWebhookEventResource::class;

    protected string $view = 'filament.shared.whatsapp.view-webhook-event';

    public bool $showRawPayload = true;

    protected function getHeaderActions(): array
    {
        return [
            ReprocessWhatsAppWebhookFilamentAction::make(),
        ];
    }
}
