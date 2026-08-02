<?php

namespace App\Filament\Crm\Exports;

use App\Models\Tenant\OpportunityFollowUp;
use App\Models\TenantUser;
use App\Support\Crm\CrmReportAccess;
use App\Support\Export\SafeExportColumn;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class FollowUpReportExporter extends Exporter
{
    protected static ?string $model = OpportunityFollowUp::class;

    public static function getName(): string
    {
        return __('crm.reports.followup.export.name');
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('scheduled_at')
                ->label(__('crm.fields.scheduled_at')),
            ExportColumn::make('completed_at')
                ->label(__('crm.fields.completed_at')),
            SafeExportColumn::translatable('followUpType.name', __('crm.fields.follow_up_type')),
            SafeExportColumn::translatable('followUpStatus.name', __('crm.fields.follow_up_status')),
            SafeExportColumn::text('assignedTo.name', __('crm.fields.assigned_to')),
            SafeExportColumn::text('opportunity.title', __('crm.fields.opportunity')),
            SafeExportColumn::translatable('opportunity.client.name', __('crm.fields.client')),
            SafeExportColumn::translatable('opportunity.branch.name', __('crm.fields.branch')),
            ExportColumn::make('scheduling_state')
                ->label(__('crm.reports.followup.columns.scheduling_state')),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        $user = auth()->user();

        abort_unless($user instanceof TenantUser && CrmReportAccess::canExportFollowUpReports($user), 403);

        return $query;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('crm.reports.followup.export.completed', [
            'count' => number_format($export->successful_rows),
        ]);
    }
}
