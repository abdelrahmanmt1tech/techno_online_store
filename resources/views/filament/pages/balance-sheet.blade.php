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
                <table class="min-w-full divide-y
                 w-full
                divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-950">
                        <tr>
                            <th scope="col" class="px-3 py-2 text-start text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('dashboard.pages.balance_sheet.account_name') }}
                            </th>
                            @foreach (($this->branches ?? []) as $branch)
                                <th scope="col" class="px-3 py-2 text-start text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    {{ $branch['name'] }}
                                </th>
                            @endforeach
                            <th scope="col" class="px-3 py-2 text-start text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ __('dashboard.pages.balance_sheet.total_all_branches') }}
                            </th>
                            <th scope="col" class="px-3 py-2 text-start text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ __('dashboard.pages.balance_sheet.total_debit') }}
                            </th>
                            <th scope="col" class="px-3 py-2 text-start text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ __('dashboard.pages.balance_sheet.total_credit') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y
bg-white
                    divide-gray-100 dark:divide-gray-800">
                        @forelse (($this->tableData ?? []) as $section)
                            {{-- Section header (main AccountTree) --}}
                            <tr class="bg-gray-100 dark:bg-gray-900/60">
                                <td colspan="{{ 1 + count($this->branches ?? []) + 3 }}"
                                    class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $section['account_name'] ?? '-' }}
                                </td>
                            </tr>

                            {{-- Rows (subAccounts) --}}
                            @foreach (($section['subAccounts'] ?? []) as $row)
                                <tr>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200 whitespace-nowrap">
                                        {{ $row['account_name'] ?? '-' }}
                                    </td>

                                    @foreach (($this->branches ?? []) as $branch)
                                        @php
                                            $value = $row['branch_calcs'][$branch['id']] ?? 0;
                                            $isNonZero = is_numeric($value) && abs((float) $value) > 0;
                                        @endphp
                                        <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200 whitespace-nowrap">
                                            @if ($isNonZero)
                                                <x-filament::badge color="primary">
                                                    {{ number_format((float) $value, 2) }}
                                                </x-filament::badge>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500">
                                                    {{ is_numeric($value) ? number_format((float) $value, 2) : $value }}
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <td class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                        @php
                                            $rowTotal = (float) ($row['row_total'] ?? 0);
                                        @endphp
                                        @if (abs($rowTotal) > 0)
                                            <x-filament::badge color="primary">
                                                {{ number_format($rowTotal, 2) }}
                                            </x-filament::badge>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">{{ number_format($rowTotal, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200 whitespace-nowrap">
                                        @php
                                            $rowDebit = (float) ($row['debit_total'] ?? 0);
                                        @endphp
                                        @if (abs($rowDebit) > 0)
                                            <x-filament::badge color="danger">
                                                {{ number_format($rowDebit, 2) }}
                                            </x-filament::badge>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">{{ number_format($rowDebit, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200 whitespace-nowrap">
                                        @php
                                            $rowCredit = (float) ($row['credit_total'] ?? 0);
                                        @endphp
                                        @if (abs($rowCredit) > 0)
                                            <x-filament::badge color="success">
                                                {{ number_format($rowCredit, 2) }}
                                            </x-filament::badge>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">{{ number_format($rowCredit, 2) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Section totals (per branch column + row totals) --}}
                            <tr class="bg-gray-50 dark:bg-gray-950">
                                <td class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    {{ __('dashboard.pages.balance_sheet.section_total') }}
                                </td>
                                @foreach (($this->branches ?? []) as $branch)
                                    @php
                                        $value = $section['totals']['branch_totals'][$branch['id']] ?? 0;
                                        $isNonZero = is_numeric($value) && abs((float) $value) > 0;
                                    @endphp
                                    <td class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                        @if ($isNonZero)
                                            <x-filament::badge color="primary">
                                                {{ number_format((float) $value, 2) }}
                                            </x-filament::badge>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">{{ number_format((float) $value, 2) }}</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    @php
                                        $sectionRowTotal = (float) ($section['totals']['row_total'] ?? 0);
                                    @endphp
                                    @if (abs($sectionRowTotal) > 0)
                                        <x-filament::badge color="primary">
                                            {{ number_format($sectionRowTotal, 2) }}
                                        </x-filament::badge>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">{{ number_format($sectionRowTotal, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    @php
                                        $sectionDebitTotal = (float) ($section['totals']['debit_total'] ?? 0);
                                    @endphp
                                    @if (abs($sectionDebitTotal) > 0)
                                        <x-filament::badge color="danger">
                                            {{ number_format($sectionDebitTotal, 2) }}
                                        </x-filament::badge>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">{{ number_format($sectionDebitTotal, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    @php
                                        $sectionCreditTotal = (float) ($section['totals']['credit_total'] ?? 0);
                                    @endphp
                                    @if (abs($sectionCreditTotal) > 0)
                                        <x-filament::badge color="success">
                                            {{ number_format($sectionCreditTotal, 2) }}
                                        </x-filament::badge>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">{{ number_format($sectionCreditTotal, 2) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 1 + count($this->branches ?? []) + 3 }}"
                                    class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('dashboard.pages.balance_sheet.no_data') }}
                                </td>
                            </tr>
                        @endforelse

                        @if(!empty($this->tableData ?? []))
                            {{-- Grand totals --}}
                            <tr class="bg-gray-100 dark:bg-gray-900/60">
                                <td class="px-3 py-2 text-sm font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    {{ __('dashboard.pages.balance_sheet.grand_total') }}
                                </td>
                                @foreach (($this->branches ?? []) as $branch)
                                    @php
                                        $value = $this->grandTotals['branch_totals'][$branch['id']] ?? 0;
                                        $isNonZero = is_numeric($value) && abs((float) $value) > 0;
                                    @endphp
                                    <td class="px-3 py-2 text-sm font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                        @if ($isNonZero)
                                            <x-filament::badge color="primary">
                                                {{ number_format((float) $value, 2) }}
                                            </x-filament::badge>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">{{ number_format((float) $value, 2) }}</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 text-sm font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    @php
                                        $grandRowTotal = (float) ($this->grandTotals['row_total'] ?? 0);
                                    @endphp
                                    @if (abs($grandRowTotal) > 0)
                                        <x-filament::badge color="primary">
                                            {{ number_format($grandRowTotal, 2) }}
                                        </x-filament::badge>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">{{ number_format($grandRowTotal, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-sm font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    @php
                                        $grandDebitTotal = (float) ($this->grandTotals['debit_total'] ?? 0);
                                    @endphp
                                    @if (abs($grandDebitTotal) > 0)
                                        <x-filament::badge color="danger">
                                            {{ number_format($grandDebitTotal, 2) }}
                                        </x-filament::badge>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">{{ number_format($grandDebitTotal, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-sm font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    @php
                                        $grandCreditTotal = (float) ($this->grandTotals['credit_total'] ?? 0);
                                    @endphp
                                    @if (abs($grandCreditTotal) > 0)
                                        <x-filament::badge color="success">
                                            {{ number_format($grandCreditTotal, 2) }}
                                        </x-filament::badge>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">{{ number_format($grandCreditTotal, 2) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
