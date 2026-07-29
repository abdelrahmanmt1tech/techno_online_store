<x-filament-panels::page>
    @php($summary = $this->summary)

    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        @if ($summary === null)
            <p class="text-sm text-gray-500">{{ __('hr.empty.default') }}</p>
        @else
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
                <div>
                    <dt class="text-gray-500">{{ __('hr.fields.period') }}</dt>
                    <dd class="mt-1 font-medium">{{ $summary['period'] }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('hr.fields.status') }}</dt>
                    <dd class="mt-1 font-medium">{{ $summary['status'] }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('hr.fields.employees_count') }}</dt>
                    <dd class="mt-1 font-medium">{{ $summary['employees_count'] }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('hr.reports.total_base_salary') }}</dt>
                    <dd class="mt-1 font-medium">{{ number_format($summary['total_base_salary'], 2) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('hr.reports.total_absence_deduction') }}</dt>
                    <dd class="mt-1 font-medium">{{ number_format($summary['total_absence_deduction'], 2) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('hr.reports.total_late_deduction') }}</dt>
                    <dd class="mt-1 font-medium">{{ number_format($summary['total_late_deduction'], 2) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('hr.reports.total_manual_deduction') }}</dt>
                    <dd class="mt-1 font-medium">{{ number_format($summary['total_manual_deduction'], 2) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('hr.reports.total_net_salary') }}</dt>
                    <dd class="mt-1 font-medium">{{ number_format($summary['total_net_salary'], 2) }}</dd>
                </div>
            </dl>
        @endif
    </div>
</x-filament-panels::page>
