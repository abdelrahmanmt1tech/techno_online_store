<?php

namespace App\Filament\Resources\WhatsAppWebhookEvents\Pages;

use App\Filament\Resources\WhatsAppWebhookEvents\WhatsAppWebhookEventResource;
use App\Filament\Shared\WhatsApp\Actions\ReprocessWhatsAppWebhookFilamentAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewWhatsAppWebhookEvent extends ViewRecord
{
    protected static string $resource = WhatsAppWebhookEventResource::class;

    protected string $view = 'filament.shared.whatsapp.view-webhook-event';

    public bool $showRawPayload = false;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->showRawPayload = Auth::user()?->can('whatsapp.platform.troubleshoot') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            ReprocessWhatsAppWebhookFilamentAction::make(),
        ];
    }
}
