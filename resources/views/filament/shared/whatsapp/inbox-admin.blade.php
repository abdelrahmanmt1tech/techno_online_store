<x-filament-panels::page>
    <div class="wa-page">
        @include('filament.shared.whatsapp.tenant-selector')

        @if (blank($selectedTenantId))
            <div class="wa-empty-state">
                {{ __('dashboard.whatsapp_select_tenant_required') }}
            </div>
        @else
            @include('filament.shared.whatsapp.inbox')
        @endif
    </div>
</x-filament-panels::page>
