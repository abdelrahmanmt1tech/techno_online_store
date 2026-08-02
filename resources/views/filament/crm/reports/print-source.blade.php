@extends('filament.crm.reports.print-layout')

@section('kpi')
    <div>
        {{ __('dashboard.crm.reports.source.stats.opportunities_total') }}: {{ number_format($summary['opportunities_total'] ?? 0) }} |
        {{ __('dashboard.crm.reports.source.stats.conversion_rate') }}: {{ number_format($summary['conversion_rate'] ?? 0, 2) }}%
    </div>
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>{{ __('dashboard.crm.reports.source.columns.source') }}</th>
                <th>{{ __('dashboard.crm.reports.source.columns.clients_count') }}</th>
                <th>{{ __('dashboard.crm.reports.source.columns.opportunities_count') }}</th>
                <th>{{ __('dashboard.crm.reports.source.columns.won_count') }}</th>
                <th>{{ __('dashboard.crm.reports.source.columns.lost_count') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ is_array($row->name) ? ($row->name[app()->getLocale()] ?? reset($row->name)) : $row->name }}</td>
                    <td>{{ $row->clients_count }}</td>
                    <td>{{ $row->opportunities_count }}</td>
                    <td>{{ $row->won_opportunities_count }}</td>
                    <td>{{ $row->lost_opportunities_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
