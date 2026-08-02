<x-filament-panels::page>
    <x-filament-widgets::widgets
        class="mb-6"
        :columns="1"
        :widgets="$this->getHeaderWidgets()"
        :data="$this->getHeaderWidgetsData()"
    />

    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
