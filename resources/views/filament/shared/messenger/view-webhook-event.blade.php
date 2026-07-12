<x-filament-panels::page>
    @include('filament.shared.messenger.webhook-event-details', [
        'event' => $this->record->loadMissing('tenant'),
        'showRawPayload' => $showRawPayload,
    ])
</x-filament-panels::page>
