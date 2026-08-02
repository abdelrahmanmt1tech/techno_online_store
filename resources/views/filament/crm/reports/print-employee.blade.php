@extends('filament.crm.reports.print-layout')

@section('kpi')
    <div>
        {{ __('crm.reports.employee.stats.employees_count') }}: {{ number_format($summary['employees_count'] ?? 0) }} |
        {{ __('crm.reports.employee.stats.effective_commissions_total') }}: {{ $summary['effective_commissions_total'] ?? '0.00' }}
    </div>
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>{{ __('crm.reports.employee.columns.employee') }}</th>
                <th>{{ __('crm.reports.employee.columns.won') }}</th>
                <th>{{ __('crm.fields.agreed_amount') }}</th>
                <th>{{ __('crm.reports.employee.columns.effective_commissions') }}</th>
                <th>{{ __('crm.reports.employee.columns.remaining') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php($totals = \App\Services\Crm\Reports\EmployeePerformanceReportQuery::commissionTotalsFor($row->id))
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ number_format((int) $row->won_opportunities_count) }}</td>
                    <td>{{ number_format((float) $row->agreed_amount_total, 2) }}</td>
                    <td>{{ $totals['effective'] }}</td>
                    <td>{{ $totals['remaining'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
