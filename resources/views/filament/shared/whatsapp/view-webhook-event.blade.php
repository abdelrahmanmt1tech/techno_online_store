<x-filament-panels::page>
    @include('filament.shared.whatsapp.webhook-event-details', [
        'event' => $this->record->loadMissing('tenant'),
        'showRawPayload' => $showRawPayload,
    ])
</x-filament-panels::page>
