@extends('filament.crm.reports.print-layout')

@section('kpi')
    <div>
        {{ __('dashboard.crm.reports.followup.stats.total') }}: {{ number_format($summary['total'] ?? 0) }} |
        {{ __('dashboard.crm.reports.followup.stats.overdue') }}: {{ number_format($summary['overdue'] ?? 0) }} |
        {{ __('dashboard.crm.reports.followup.stats.completed') }}: {{ number_format($summary['completed'] ?? 0) }}
    </div>
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>{{ __('dashboard.crm.fields.scheduled_at') }}</th>
                <th>{{ __('dashboard.crm.fields.follow_up_type') }}</th>
                <th>{{ __('dashboard.crm.fields.assigned_to') }}</th>
                <th>{{ __('dashboard.crm.fields.opportunity') }}</th>
                <th>{{ __('dashboard.crm.fields.client') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row->scheduled_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>{{ is_array($row->followUpType?->name) ? ($row->followUpType->name[app()->getLocale()] ?? reset($row->followUpType->name)) : ($row->followUpType?->name ?? '-') }}</td>
                    <td>{{ $row->assignedTo?->name ?? '-' }}</td>
                    <td>{{ $row->opportunity?->title ?? '-' }}</td>
                    <td>{{ is_array($row->opportunity?->client?->name) ? ($row->opportunity->client->name[app()->getLocale()] ?? reset($row->opportunity->client->name)) : ($row->opportunity?->client?->name ?? '-') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
