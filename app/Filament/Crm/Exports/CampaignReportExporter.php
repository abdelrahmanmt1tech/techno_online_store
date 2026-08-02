<?php

namespace App\Filament\Crm\Exports;

use App\Models\Tenant\Campaign;
use App\Models\TenantUser;
use App\Services\Crm\Reports\CampaignReportQuery;
use App\Services\Crm\Reports\CrmReportMetrics;
use App\Support\Crm\CrmReportAccess;
use App\Support\Export\SafeExportColumn;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class CampaignReportExporter extends Exporter
{
    protected static ?string $model = Campaign::class;

    public static function getName(): string
    {
        return __('crm.reports.campaign.export.name');
    }

    public static function getColumns(): array
    {
        return [
            SafeExportColumn::translatable('name', __('crm.reports.campaign.columns.campaign')),
            ExportColumn::make('status')
                ->label(__('crm.fields.status')),
            ExportColumn::make('budget')
                ->label(__('crm.reports.campaign.columns.budget')),
            ExportColumn::make('opportunities_count')
                ->label(__('crm.reports.campaign.columns.opportunities_count')),
            ExportColumn::make('won_opportunities_count')
                ->label(__('crm.reports.campaign.columns.won_count')),
            ExportColumn::make('lost_opportunities_count')
                ->label(__('crm.reports.campaign.columns.lost_count')),
            ExportColumn::make('amount_total')
                ->label(__('crm.fields.amount')),
            ExportColumn::make('agreed_amount_total')
                ->label(__('crm.fields.agreed_amount')),
            ExportColumn::make('conversion_rate')
                ->label(__('crm.reports.campaign.columns.conversion_rate'))
                ->state(fn (Campaign $record): string => CampaignReportQuery::conversionRate($record)),
            ExportColumn::make('cost_per_opportunity')
                ->label(__('crm.reports.campaign.columns.cost_per_opportunity'))
                ->state(fn (Campaign $record): string => CampaignReportQuery::costPerOpportunity($record)),
            ExportColumn::make('cost_per_won')
                ->label(__('crm.reports.campaign.columns.cost_per_won'))
                ->state(fn (Campaign $record): string => CampaignReportQuery::costPerWonOpportunity($record)),
            ExportColumn::make('expected_roi')
                ->label(__('crm.reports.campaign.columns.expected_roi'))
                ->state(function (Campaign $record): string {
                    $roi = CampaignReportQuery::expectedRoi($record);

                    return $roi === CrmReportMetrics::NOT_APPLICABLE ? $roi : $roi;
                }),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        $user = auth()->user();

        abort_unless($user instanceof TenantUser && CrmReportAccess::canExportCampaignReports($user), 403);

        return $query;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('crm.reports.campaign.export.completed', [
            'count' => number_format($export->successful_rows),
        ]);
    }
}
