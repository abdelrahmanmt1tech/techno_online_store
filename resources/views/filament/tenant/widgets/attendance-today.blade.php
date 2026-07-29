<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('dashboard.lite.attendance_today') }}
        </x-slot>

        @if ($records->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.lite.empty_attendance') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-start text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_employee') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_status') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_check_in') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_late_minutes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pe-3">{{ $record->employee?->full_name ?: '—' }}</td>
                                <td class="py-2 pe-3">{{ $record->status?->label() }}</td>
                                <td class="py-2 pe-3">{{ optional($record->check_in_at)?->format('H:i') ?: '—' }}</td>
                                <td class="py-2 pe-3">{{ (int) $record->late_minutes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
