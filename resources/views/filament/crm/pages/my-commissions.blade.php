<x-filament-panels::page>
    @php($totals = $this->totals)

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.totals.original') }}</p>
            <p class="text-xl font-semibold">{{ number_format((float) $totals['original_total'], 2) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.totals.effective') }}</p>
            <p class="text-xl font-semibold">{{ number_format((float) $totals['effective_total'], 2) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.totals.net_paid') }}</p>
            <p class="text-xl font-semibold">{{ number_format((float) $totals['net_paid_total'], 2) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.totals.remaining') }}</p>
            <p class="text-xl font-semibold">{{ number_format((float) $totals['remaining_total'], 2) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.totals.increase_adjustments') }}</p>
            <p class="text-xl font-semibold">{{ number_format((float) $totals['approved_increase_total'], 2) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.totals.decrease_adjustments') }}</p>
            <p class="text-xl font-semibold">{{ number_format((float) $totals['approved_decrease_total'], 2) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.totals.pending_count') }}</p>
            <p class="text-xl font-semibold">{{ $totals['pending_count'] }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.totals.opportunity_count') }}</p>
            <p class="text-xl font-semibold">{{ $totals['opportunity_count'] }}</p>
        </x-filament::section>
    </div>

    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
