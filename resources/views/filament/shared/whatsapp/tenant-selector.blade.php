<div class="wa-tenant-selector">
    <label class="wa-tenant-selector__label" for="wa-tenant-select">{{ __('dashboard.whatsapp_select_tenant') }}</label>
    <select id="wa-tenant-select" wire:model.live="selectedTenantId" class="wa-select">
        <option value="">{{ __('dashboard.whatsapp_select_tenant_required') }}</option>
        @foreach ($this->tenants as $tenant)
            <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
        @endforeach
    </select>
</div>
