<x-filament-panels::page>
    <div class="acc-report">
        <form method="GET" class="acc-report__filters">
            <div class="acc-report__field">
                <label>{{ __('dashboard.resources.operation.financial_period') }}</label>
                <select name="financial_period_id" class="fi-input">
                    <option value="">{{ __('dashboard.resources.financial_period.all_periods') }}</option>
                    @foreach (\App\Models\Tenant\FinancialPeriod::query()->orderByDesc('start_date')->get(['id', 'name']) as $periodOption)
                        <option value="{{ $periodOption->id }}" @selected((int) request('financial_period_id') === (int) $periodOption->id)>{{ $periodOption->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="acc-report__field">
                <label>{{ __('dashboard.fields.from_date') }}</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="fi-input" />
            </div>
            <div class="acc-report__field">
                <label>{{ __('dashboard.fields.to_date') }}</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="fi-input" />
            </div>
            <div class="acc-report__field">
                <x-filament::button type="submit" class="acc-report__submit">
                    {{ __('dashboard.resources.financial_period.apply_filters') }}
                </x-filament::button>
            </div>
        </form>

        <div class="acc-report__panel">
            <div class="acc-report__scroll">
                <table class="acc-report__table">
                    <thead>
                        <tr>
                            <th>{{ __('dashboard.pages.balance_sheet.account_name') }}</th>
                            @foreach (($this->branches ?? []) as $branch)
                                <th>{{ $branch['name'] }}</th>
                            @endforeach
                            <th>{{ __('dashboard.pages.balance_sheet.total_all_branches') }}</th>
                            <th>{{ __('dashboard.pages.balance_sheet.total_debit') }}</th>
                            <th>{{ __('dashboard.pages.balance_sheet.total_credit') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($this->tableData ?? []) as $section)
                            <tr class="acc-report__section-row">
                                <td colspan="{{ 1 + count($this->branches ?? []) + 3 }}">
                                    {{ $section['account_name'] ?? '-' }}
                                </td>
                            </tr>

                            @foreach (($section['subAccounts'] ?? []) as $row)
                                <tr>
                                    <td>{{ $row['account_name'] ?? '-' }}</td>
                                    @foreach (($this->branches ?? []) as $branch)
                                        @php $value = (float) ($row['branch_calcs'][$branch['id']] ?? 0); @endphp
                                        <td>
                                            @if (abs($value) > 0)
                                                <x-filament::badge color="primary">{{ number_format($value, 2) }}</x-filament::badge>
                                            @else
                                                <span class="acc-report__muted">{{ number_format($value, 2) }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    @php
                                        $rowTotal = (float) ($row['row_total'] ?? 0);
                                        $rowDebit = (float) ($row['debit_total'] ?? 0);
                                        $rowCredit = (float) ($row['credit_total'] ?? 0);
                                    @endphp
                                    <td>
                                        @if (abs($rowTotal) > 0)
                                            <x-filament::badge color="primary">{{ number_format($rowTotal, 2) }}</x-filament::badge>
                                        @else
                                            <span class="acc-report__muted">{{ number_format($rowTotal, 2) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (abs($rowDebit) > 0)
                                            <x-filament::badge color="danger">{{ number_format($rowDebit, 2) }}</x-filament::badge>
                                        @else
                                            <span class="acc-report__muted">{{ number_format($rowDebit, 2) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (abs($rowCredit) > 0)
                                            <x-filament::badge color="success">{{ number_format($rowCredit, 2) }}</x-filament::badge>
                                        @else
                                            <span class="acc-report__muted">{{ number_format($rowCredit, 2) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            <tr class="acc-report__totals-row">
                                <td>{{ __('dashboard.pages.balance_sheet.section_total') }}</td>
                                @foreach (($this->branches ?? []) as $branch)
                                    @php $value = (float) ($section['totals']['branch_totals'][$branch['id']] ?? 0); @endphp
                                    <td>
                                        @if (abs($value) > 0)
                                            <x-filament::badge color="primary">{{ number_format($value, 2) }}</x-filament::badge>
                                        @else
                                            <span class="acc-report__muted">{{ number_format($value, 2) }}</span>
                                        @endif
                                    </td>
                                @endforeach
                                @php
                                    $sectionRowTotal = (float) ($section['totals']['row_total'] ?? 0);
                                    $sectionDebitTotal = (float) ($section['totals']['debit_total'] ?? 0);
                                    $sectionCreditTotal = (float) ($section['totals']['credit_total'] ?? 0);
                                @endphp
                                <td>
                                    @if (abs($sectionRowTotal) > 0)
                                        <x-filament::badge color="primary">{{ number_format($sectionRowTotal, 2) }}</x-filament::badge>
                                    @else
                                        <span class="acc-report__muted">{{ number_format($sectionRowTotal, 2) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if (abs($sectionDebitTotal) > 0)
                                        <x-filament::badge color="danger">{{ number_format($sectionDebitTotal, 2) }}</x-filament::badge>
                                    @else
                                        <span class="acc-report__muted">{{ number_format($sectionDebitTotal, 2) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if (abs($sectionCreditTotal) > 0)
                                        <x-filament::badge color="success">{{ number_format($sectionCreditTotal, 2) }}</x-filament::badge>
                                    @else
                                        <span class="acc-report__muted">{{ number_format($sectionCreditTotal, 2) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 1 + count($this->branches ?? []) + 3 }}" class="acc-report__empty">
                                    {{ __('dashboard.pages.balance_sheet.no_data') }}
                                </td>
                            </tr>
                        @endforelse

                        @if (! empty($this->tableData ?? []))
                            <tr class="acc-report__section-row">
                                <td>{{ __('dashboard.pages.balance_sheet.grand_total') }}</td>
                                @foreach (($this->branches ?? []) as $branch)
                                    @php $value = (float) ($this->grandTotals['branch_totals'][$branch['id']] ?? 0); @endphp
                                    <td>
                                        @if (abs($value) > 0)
                                            <x-filament::badge color="primary">{{ number_format($value, 2) }}</x-filament::badge>
                                        @else
                                            <span class="acc-report__muted">{{ number_format($value, 2) }}</span>
                                        @endif
                                    </td>
                                @endforeach
                                @php
                                    $grandRowTotal = (float) ($this->grandTotals['row_total'] ?? 0);
                                    $grandDebitTotal = (float) ($this->grandTotals['debit_total'] ?? 0);
                                    $grandCreditTotal = (float) ($this->grandTotals['credit_total'] ?? 0);
                                @endphp
                                <td>
                                    @if (abs($grandRowTotal) > 0)
                                        <x-filament::badge color="primary">{{ number_format($grandRowTotal, 2) }}</x-filament::badge>
                                    @else
                                        <span class="acc-report__muted">{{ number_format($grandRowTotal, 2) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if (abs($grandDebitTotal) > 0)
                                        <x-filament::badge color="danger">{{ number_format($grandDebitTotal, 2) }}</x-filament::badge>
                                    @else
                                        <span class="acc-report__muted">{{ number_format($grandDebitTotal, 2) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if (abs($grandCreditTotal) > 0)
                                        <x-filament::badge color="success">{{ number_format($grandCreditTotal, 2) }}</x-filament::badge>
                                    @else
                                        <span class="acc-report__muted">{{ number_format($grandCreditTotal, 2) }}</span>
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
