<?php

namespace App\Filament\Crm\Exports;

use App\Models\Tenant\LeadSource;
use App\Models\TenantUser;
use App\Support\Crm\CrmReportAccess;
use App\Support\Export\SafeExportColumn;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class SourceReportExporter extends Exporter
{
    protected static ?string $model = LeadSource::class;

    public static function getName(): string
    {
        return __('crm.reports.source.export.name');
    }

    public static function getColumns(): array
    {
        return [
            SafeExportColumn::translatable('name', __('crm.reports.source.columns.source')),
            ExportColumn::make('clients_count')
                ->label(__('crm.reports.source.columns.clients_count')),
            ExportColumn::make('opportunities_count')
                ->label(__('crm.reports.source.columns.opportunities_count')),
            ExportColumn::make('open_opportunities_count')
                ->label(__('crm.reports.source.columns.open_count')),
            ExportColumn::make('won_opportunities_count')
                ->label(__('crm.reports.source.columns.won_count')),
            ExportColumn::make('lost_opportunities_count')
                ->label(__('crm.reports.source.columns.lost_count')),
            ExportColumn::make('amount_total')
                ->label(__('crm.fields.amount')),
            ExportColumn::make('agreed_amount_total')
                ->label(__('crm.fields.agreed_amount')),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        $user = auth()->user();

        abort_unless($user instanceof User && CrmReportAccess::canExportSourceReports($user), 403);

        return $query;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('crm.reports.source.export.completed', [
            'count' => number_format($export->successful_rows),
        ]);
    }
}
