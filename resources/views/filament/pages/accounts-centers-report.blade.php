<x-filament-panels::page>
    {{-- <div class="mb-4 flex justify-end">
        <x-filament::button
            color="gray"
            icon="heroicon-m-arrow-down-tray"
            wire:click="exportExcel"
        >
            {{ __('dashboard.exports.download_excel') }}
        </x-filament::button>
    </div> --}}
    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>

