@extends('filament.crm.reports.print-layout')

@section('kpi')
    <div>
        {{ __('crm.reports.customer.stats.total_clients') }}: {{ number_format($summary['total_clients'] ?? 0) }} |
        {{ __('crm.reports.customer.stats.conversion_rate') }}: {{ number_format($summary['conversion_rate'] ?? 0, 2) }}%
    </div>
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>{{ __('crm.fields.client') }}</th>
                <th>{{ __('crm.fields.stage') }}</th>
                <th>{{ __('crm.reports.customer.columns.opportunities_count') }}</th>
                <th>{{ __('crm.reports.customer.columns.won_opportunities_count') }}</th>
                <th>{{ __('crm.reports.customer.columns.agreed_amount_total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ is_array($row->name) ? ($row->name[app()->getLocale()] ?? reset($row->name)) : $row->name }}</td>
                    <td>{{ $row->stage?->label() }}</td>
                    <td>{{ $row->opportunities_count }}</td>
                    <td>{{ $row->won_opportunities_count }}</td>
                    <td>{{ number_format((float) ($row->opportunities_agreed_amount_total ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
