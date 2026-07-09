<x-filament-panels::page>
    <div class="mb-4">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('dashboard.whatsapp_select_tenant') }}</label>
        <select wire:model.live="selectedTenantId" class="fi-input mt-1 block w-full max-w-md rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900">
            <option value="">{{ __('dashboard.whatsapp_select_tenant_required') }}</option>
            @foreach ($this->tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
            @endforeach
        </select>
    </div>

    @if (blank($selectedTenantId))
        <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-8 text-sm text-gray-500">
            {{ __('dashboard.whatsapp_select_tenant_required') }}
        </div>
    @else
        @include('filament.shared.whatsapp.inbox')
    @endif
</x-filament-panels::page>
