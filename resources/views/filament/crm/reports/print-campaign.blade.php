@extends('filament.crm.reports.print-layout')

@section('kpi')
    <div>
        {{ __('crm.reports.campaign.stats.campaigns_count') }}: {{ number_format($summary['campaigns_count'] ?? 0) }} |
        {{ __('crm.reports.campaign.stats.expected_roi') }}: {{ \App\Services\Crm\Reports\CrmReportMetrics::displayPercent($summary['expected_roi'] ?? '0.00') }}
    </div>
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>{{ __('crm.reports.campaign.columns.campaign') }}</th>
                <th>{{ __('crm.reports.campaign.columns.budget') }}</th>
                <th>{{ __('crm.reports.campaign.columns.opportunities_count') }}</th>
                <th>{{ __('crm.reports.campaign.columns.won_count') }}</th>
                <th>{{ __('crm.reports.campaign.columns.expected_roi') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php($roi = \App\Services\Crm\Reports\CampaignReportQuery::expectedRoi($row))
                <tr>
                    <td>{{ is_array($row->name) ? ($row->name[app()->getLocale()] ?? reset($row->name)) : ($row->name ?? '-') }}</td>
                    <td>{{ number_format((float) $row->budget, 2) }}</td>
                    <td>{{ number_format((int) $row->opportunities_count) }}</td>
                    <td>{{ number_format((int) $row->won_opportunities_count) }}</td>
                    <td>{{ \App\Services\Crm\Reports\CrmReportMetrics::displayPercent($roi) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
