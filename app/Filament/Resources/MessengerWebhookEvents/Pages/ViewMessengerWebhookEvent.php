<?php

namespace App\Filament\Resources\MessengerWebhookEvents\Pages;

use App\Filament\Resources\MessengerWebhookEvents\MessengerWebhookEventResource;
use App\Filament\Shared\Messenger\Actions\ReprocessMessengerWebhookFilamentAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewMessengerWebhookEvent extends ViewRecord
{
    protected static string $resource = MessengerWebhookEventResource::class;

    protected string $view = 'filament.shared.messenger.view-webhook-event';

    public bool $showRawPayload = false;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->showRawPayload = (bool) (
            config('app.bypass_permissions')
            || Auth::user()?->can('messenger.platform.troubleshoot')
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            ReprocessMessengerWebhookFilamentAction::make(),
        ];
    }
}
