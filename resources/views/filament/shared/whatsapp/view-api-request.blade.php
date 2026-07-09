<x-filament-panels::page>
    @include('filament.shared.whatsapp.api-request-details', [
        'request' => $this->record->loadMissing('whatsappNumber'),
        'showTechnical' => true,
    ])
</x-filament-panels::page>
