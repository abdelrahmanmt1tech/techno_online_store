@extends('filament.crm.reports.print-layout')

@section('kpi')
    <div>
        {{ __('dashboard.crm.reports.opportunity.stats.total') }}: {{ number_format($summary['total'] ?? 0) }} |
        {{ __('dashboard.crm.reports.opportunity.stats.won') }}: {{ number_format($summary['won'] ?? 0) }} |
        {{ __('dashboard.crm.reports.opportunity.stats.lost') }}: {{ number_format($summary['lost'] ?? 0) }}
    </div>
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>{{ __('dashboard.crm.fields.title') }}</th>
                <th>{{ __('dashboard.crm.fields.client') }}</th>
                <th>{{ __('dashboard.crm.fields.amount') }}</th>
                <th>{{ __('dashboard.crm.fields.agreed_amount') }}</th>
                <th>{{ __('dashboard.crm.fields.closed_at') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row->title }}</td>
                    <td>{{ is_array($row->client?->name) ? ($row->client->name[app()->getLocale()] ?? reset($row->client->name)) : ($row->client?->name ?? '-') }}</td>
                    <td>{{ number_format((float) $row->amount, 2) }}</td>
                    <td>{{ number_format((float) $row->agreed_amount, 2) }}</td>
                    <td>{{ $row->closed_at?->format('Y-m-d') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
