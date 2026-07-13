<x-filament-panels::page>
    @include('filament.shared.messenger.api-request-details', [
        'request' => $this->record->loadMissing('messengerPage'),
        'showTechnical' => true,
    ])
</x-filament-panels::page>
