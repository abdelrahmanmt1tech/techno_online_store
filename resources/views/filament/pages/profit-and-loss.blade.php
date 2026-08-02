<x-filament-panels::page>
    <form method="GET" class="mb-4 grid gap-4 md:grid-cols-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ __('dashboard.resources.operation.financial_period') }}
            </label>
            <select name="financial_period_id" class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                <option value="">{{ __('dashboard.resources.financial_period.all_periods') }}</option>
                @foreach (\App\Models\Tenant\FinancialPeriod::query()->orderByDesc('start_date')->get(['id', 'name']) as $periodOption)
                    <option value="{{ $periodOption->id }}" @selected((int) request('financial_period_id') === (int) $periodOption->id)>{{ $periodOption->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ __('dashboard.fields.from_date') }}
            </label>
            <input type="date" name="from_date" value="{{ request('from_date') }}" class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" />
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ __('dashboard.fields.to_date') }}
            </label>
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" />
        </div>
        <div class="flex items-end">
            <button type="submit" class="fi-btn fi-btn-color-primary fi-color-primary w-full rounded-lg px-4 py-2 text-sm font-medium text-white">
                {{ __('dashboard.resources.financial_period.apply_filters') }}
            </button>
        </div>
    </form>

    <div class="fi-section">
        <div class="fi-section-content-ctn">
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="min-w-full w-full
                 divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-950">
                        <tr>
                            <th scope="col" class="px-3 py-2 text-start text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('dashboard.pages.profit_and_loss.description') }}
                            </th>
                            <th scope="col" class="px-3 py-2 text-start text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ __('dashboard.pages.profit_and_loss.current_month') }}
                            </th>
                            <th scope="col" class="px-3 py-2 text-start text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ __('dashboard.pages.profit_and_loss.current_month_pct') }}
                            </th>
                            <th scope="col" class="px-3 py-2 text-start text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ __('dashboard.pages.profit_and_loss.ytd') }}
                            </th>
                            <th scope="col" class="px-3 py-2 text-start text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ __('dashboard.pages.profit_and_loss.ytd_pct') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white">
                        @php
                            $salesCurrent = (float) ($this->salesTotals['current'] ?? 0);
                            $salesYtd = (float) ($this->salesTotals['ytd'] ?? 0);
                        @endphp

                        @if ($salesYtd > 0)
                            <tr class="bg-gray-50 dark:bg-gray-950">
                                <td class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ __('dashboard.pages.profit_and_loss.sales_total_label') }}
                                </td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    @if (abs($salesCurrent) > 0)
                                        <x-filament::badge color="primary">{{ number_format($salesCurrent, 2) }}</x-filament::badge>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">{{ number_format($salesCurrent, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    <x-filament::badge color="success">100%</x-filament::badge>
                                </td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    <x-filament::badge color="primary">{{ number_format($salesYtd, 2) }}</x-filament::badge>
                                </td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    <x-filament::badge color="success">100%</x-filament::badge>
                                </td>
                            </tr>
                        @endif

                        @forelse (($this->tableData ?? []) as $section)
                            {{-- Section header --}}
                            <tr class="bg-gray-100 dark:bg-gray-900/60">
                                <td colspan="5" class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $section['account_name'] ?? '-' }}
                                </td>
                            </tr>

                            @foreach (($section['subAccounts'] ?? []) as $row)
                                @php
                                    $current = (float) ($row['current'] ?? 0);
                                    $currentPct = (float) ($row['current_percent'] ?? 0);
                                    $ytd = (float) ($row['ytd'] ?? 0);
                                    $pct = (float) ($row['ytd_percent'] ?? 0);
                                @endphp
                                <tr>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200 whitespace-nowrap">
                                        {{ $row['account_name'] ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-sm whitespace-nowrap">
                                        @if (abs($current) > 0)
                                            <x-filament::badge color="primary">{{ number_format($current, 2) }}</x-filament::badge>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">{{ number_format($current, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm whitespace-nowrap">
                                        @if (abs($currentPct) > 0)
                                            <x-filament::badge color="success">{{ number_format($currentPct, 2) }}%</x-filament::badge>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">0%</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm whitespace-nowrap">
                                        @if (abs($ytd) > 0)
                                            <x-filament::badge color="primary">{{ number_format($ytd, 2) }}</x-filament::badge>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">{{ number_format($ytd, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm whitespace-nowrap">
                                        @if (abs($pct) > 0)
                                            <x-filament::badge color="success">{{ number_format($pct, 2) }}%</x-filament::badge>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">0%</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Section totals --}}
                            @php
                                $tCurrent = (float) ($section['totals']['current'] ?? 0);
                                $tCurrentPct = (float) ($section['totals']['current_percent'] ?? 0);
                                $tYtd = (float) ($section['totals']['ytd'] ?? 0);
                                $tPct = (float) ($section['totals']['ytd_percent'] ?? 0);
                            @endphp
                            <tr class="bg-gray-50 dark:bg-gray-950">
                                <td class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ __('dashboard.pages.profit_and_loss.section_total', ['name' => $section['account_name'] ?? '']) }}
                                </td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    @if (abs($tCurrent) > 0)
                                        <x-filament::badge color="primary">{{ number_format($tCurrent, 2) }}</x-filament::badge>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">{{ number_format($tCurrent, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    @if (abs($tCurrentPct) > 0)
                                        <x-filament::badge color="success">{{ number_format($tCurrentPct, 2) }}%</x-filament::badge>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">0%</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    @if (abs($tYtd) > 0)
                                        <x-filament::badge color="primary">{{ number_format($tYtd, 2) }}</x-filament::badge>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">{{ number_format($tYtd, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    @if (abs($tPct) > 0)
                                        <x-filament::badge color="success">{{ number_format($tPct, 2) }}%</x-filament::badge>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">0%</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('dashboard.pages.profit_and_loss.no_data') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

