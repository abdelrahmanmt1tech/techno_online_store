<?php

namespace App\Filament\Crm\Exports;

use App\Models\Tenant\Opportunity;
use App\Models\TenantUser;
use App\Services\Crm\Reports\OpportunityReportQuery;
use App\Support\Crm\CrmReportAccess;
use App\Support\Export\SafeExportColumn;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class OpportunityReportExporter extends Exporter
{
    protected static ?string $model = Opportunity::class;

    public static function getName(): string
    {
        return __('crm.reports.opportunity.export.name');
    }

    public static function getColumns(): array
    {
        return [
            SafeExportColumn::text('title', __('crm.fields.title')),
            SafeExportColumn::translatable('client.name', __('crm.fields.client')),
            SafeExportColumn::translatable('client.leadSource.name', __('crm.fields.source')),
            SafeExportColumn::translatable('campaign.name', __('crm.fields.campaign')),
            SafeExportColumn::translatable('branch.name', __('crm.fields.branch')),
            ExportColumn::make('amount')
                ->label(__('crm.fields.amount')),
            ExportColumn::make('agreed_amount')
                ->label(__('crm.fields.agreed_amount')),
            SafeExportColumn::translatable('opportunityStage.name', __('crm.fields.stage')),
            SafeExportColumn::text('assignedTo.name', __('crm.fields.assigned_to')),
            ExportColumn::make('created_at')
                ->label(__('crm.fields.created_at')),
            ExportColumn::make('closed_at')
                ->label(__('crm.fields.closed_at')),
            ExportColumn::make('close_duration_days')
                ->label(__('crm.reports.opportunity.columns.close_duration'))
                ->state(fn (Opportunity $record): ?int => OpportunityReportQuery::closeDurationDays($record)),
            ExportColumn::make('opportunity_follow_ups_count')
                ->label(__('crm.reports.opportunity.columns.follow_ups_count')),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        $user = auth()->user();

        abort_unless($user instanceof User && CrmReportAccess::canExportOpportunityReports($user), 403);

        return $query;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('crm.reports.opportunity.export.completed', [
            'count' => number_format($export->successful_rows),
        ]);
    }
}
