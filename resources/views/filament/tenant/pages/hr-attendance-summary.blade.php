<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-x-auto">
        <table class="w-full text-sm text-start divide-y divide-gray-200 dark:divide-white/10">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('hr.fields.employee') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('hr.fields.employee_number') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('hr.fields.branch') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('hr.fields.present_days') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('hr.fields.absent_days') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('hr.fields.late_days') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('hr.fields.total_late_minutes') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($this->rows as $row)
                    <tr>
                        <td class="px-4 py-3">{{ $row['employee'] }}</td>
                        <td class="px-4 py-3">{{ $row['employee_number'] }}</td>
                        <td class="px-4 py-3">{{ $row['branch'] }}</td>
                        <td class="px-4 py-3">{{ $row['present_days'] }}</td>
                        <td class="px-4 py-3">{{ $row['absent_days'] }}</td>
                        <td class="px-4 py-3">{{ $row['late_days'] }}</td>
                        <td class="px-4 py-3">{{ $row['total_late_minutes'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">{{ __('hr.empty.default') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
