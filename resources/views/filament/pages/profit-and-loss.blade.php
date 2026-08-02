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
                            <th>{{ __('dashboard.pages.profit_and_loss.description') }}</th>
                            <th>{{ __('dashboard.pages.profit_and_loss.current_month') }}</th>
                            <th>{{ __('dashboard.pages.profit_and_loss.current_month_pct') }}</th>
                            <th>{{ __('dashboard.pages.profit_and_loss.ytd') }}</th>
                            <th>{{ __('dashboard.pages.profit_and_loss.ytd_pct') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $salesCurrent = (float) ($this->salesTotals['current'] ?? 0);
                            $salesYtd = (float) ($this->salesTotals['ytd'] ?? 0);
                        @endphp

                        @if ($salesYtd > 0)
                            <tr class="acc-report__totals-row">
                                <td>{{ __('dashboard.pages.profit_and_loss.sales_total_label') }}</td>
                                <td>
                                    @if (abs($salesCurrent) > 0)
                                        <x-filament::badge color="primary">{{ number_format($salesCurrent, 2) }}</x-filament::badge>
                                    @else
                                        <span class="acc-report__muted">{{ number_format($salesCurrent, 2) }}</span>
                                    @endif
                                </td>
                                <td><x-filament::badge color="success">100%</x-filament::badge></td>
                                <td><x-filament::badge color="primary">{{ number_format($salesYtd, 2) }}</x-filament::badge></td>
                                <td><x-filament::badge color="success">100%</x-filament::badge></td>
                            </tr>
                        @endif

                        @forelse (($this->tableData ?? []) as $section)
                            <tr class="acc-report__section-row">
                                <td colspan="5">{{ $section['account_name'] ?? '-' }}</td>
                            </tr>

                            @foreach (($section['subAccounts'] ?? []) as $row)
                                @php
                                    $current = (float) ($row['current'] ?? 0);
                                    $currentPct = (float) ($row['current_percent'] ?? 0);
                                    $ytd = (float) ($row['ytd'] ?? 0);
                                    $pct = (float) ($row['ytd_percent'] ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $row['account_name'] ?? '-' }}</td>
                                    <td>
                                        @if (abs($current) > 0)
                                            <x-filament::badge color="primary">{{ number_format($current, 2) }}</x-filament::badge>
                                        @else
                                            <span class="acc-report__muted">{{ number_format($current, 2) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (abs($currentPct) > 0)
                                            <x-filament::badge color="success">{{ number_format($currentPct, 2) }}%</x-filament::badge>
                                        @else
                                            <span class="acc-report__muted">0%</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (abs($ytd) > 0)
                                            <x-filament::badge color="primary">{{ number_format($ytd, 2) }}</x-filament::badge>
                                        @else
                                            <span class="acc-report__muted">{{ number_format($ytd, 2) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (abs($pct) > 0)
                                            <x-filament::badge color="success">{{ number_format($pct, 2) }}%</x-filament::badge>
                                        @else
                                            <span class="acc-report__muted">0%</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            @php
                                $tCurrent = (float) ($section['totals']['current'] ?? 0);
                                $tCurrentPct = (float) ($section['totals']['current_percent'] ?? 0);
                                $tYtd = (float) ($section['totals']['ytd'] ?? 0);
                                $tPct = (float) ($section['totals']['ytd_percent'] ?? 0);
                            @endphp
                            <tr class="acc-report__totals-row">
                                <td>{{ __('dashboard.pages.profit_and_loss.section_total', ['name' => $section['account_name'] ?? '']) }}</td>
                                <td>
                                    @if (abs($tCurrent) > 0)
                                        <x-filament::badge color="primary">{{ number_format($tCurrent, 2) }}</x-filament::badge>
                                    @else
                                        <span class="acc-report__muted">{{ number_format($tCurrent, 2) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if (abs($tCurrentPct) > 0)
                                        <x-filament::badge color="success">{{ number_format($tCurrentPct, 2) }}%</x-filament::badge>
                                    @else
                                        <span class="acc-report__muted">0%</span>
                                    @endif
                                </td>
                                <td>
                                    @if (abs($tYtd) > 0)
                                        <x-filament::badge color="primary">{{ number_format($tYtd, 2) }}</x-filament::badge>
                                    @else
                                        <span class="acc-report__muted">{{ number_format($tYtd, 2) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if (abs($tPct) > 0)
                                        <x-filament::badge color="success">{{ number_format($tPct, 2) }}%</x-filament::badge>
                                    @else
                                        <span class="acc-report__muted">0%</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="acc-report__empty">
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
