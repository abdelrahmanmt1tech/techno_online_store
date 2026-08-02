<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
    <style>
        body { font-family: Arial, 'Noto Sans Arabic', sans-serif; font-size: 11pt; margin: 1.5rem; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #cbd5e1; padding: 0.4rem 0.5rem; text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; }
        th { background: #f1f5f9; }
        .meta { margin-bottom: 1rem; }
        .filters { margin-bottom: 1rem; }
        .filters ul { margin: 0.25rem 0 0; padding-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 1.25rem; }
        .kpi { margin-bottom: 1rem; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 1rem;">
        <button onclick="window.print()">{{ __('dashboard.crm.reports.actions.print') }}</button>
    </div>
    <h1>{{ $reportTitle }}</h1>
    <div class="meta">
        <div>{{ __('dashboard.crm.reports.print.generated_at') }}: {{ $printedAt->format('Y-m-d H:i') }}</div>
        <div>{{ __('dashboard.crm.reports.print.generated_by') }}: {{ $printedBy }}</div>
        <div>{{ __('dashboard.crm.reports.common.row_count') }}: {{ $rowCount }}@if($rowCount >= $maxRows) ({{ __('dashboard.crm.reports.print.row_limit_reached', ['max' => number_format($maxRows)]) }})@endif</div>
    </div>

    @if (! empty($summaryLines))
        <div class="filters">
            <strong>{{ __('dashboard.crm.reports.print.applied_filters') }}</strong>
            <ul>
                @foreach ($summaryLines as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @hasSection('kpi')
        <div class="kpi">
            @yield('kpi')
        </div>
    @endif

    @yield('content')
</body>
</html>
