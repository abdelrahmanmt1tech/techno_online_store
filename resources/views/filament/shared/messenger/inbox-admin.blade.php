<x-filament-panels::page>
    <div class="wa-page">
        @include('filament.shared.messenger.tenant-selector')

        @if (blank($selectedTenantId))
            <div class="wa-empty-state">
                {{ __('dashboard.messenger_select_tenant_required') }}
            </div>
        @else
            @include('filament.shared.messenger.inbox')
        @endif
    </div>
</x-filament-panels::page>
