<div class="wa-tenant-selector">
    <label class="wa-tenant-selector__label" for="messenger-tenant-select">{{ __('dashboard.messenger_select_tenant') }}</label>
    <select id="messenger-tenant-select" wire:model.live="selectedTenantId" class="wa-select">
        <option value="">{{ __('dashboard.messenger_select_tenant_required') }}</option>
        @foreach ($this->tenants as $tenant)
            <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
        @endforeach
    </select>
</div>
