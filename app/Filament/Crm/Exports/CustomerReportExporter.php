<?php

namespace App\Filament\Crm\Exports;

use App\Enums\Crm\ClientStage;
use App\Models\Tenant\Client;
use App\Models\TenantUser;
use App\Support\Crm\CrmReportAccess;
use App\Support\Export\SafeExportColumn;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class CustomerReportExporter extends Exporter
{
    protected static ?string $model = Client::class;

    public static function getName(): string
    {
        return __('crm.reports.customer.export.name');
    }

    public static function getColumns(): array
    {
        return [
            SafeExportColumn::translatable('name', __('crm.fields.client')),
            ExportColumn::make('stage')
                ->label(__('crm.fields.stage'))
                ->formatStateUsing(fn (ClientStage $state): string => $state->label()),
            SafeExportColumn::translatable('leadSource.name', __('crm.fields.source')),
            SafeExportColumn::text('salesRep.name', __('crm.fields.assigned_to')),
            ExportColumn::make('opportunities_count')
                ->label(__('crm.reports.customer.columns.opportunities_count')),
            ExportColumn::make('won_opportunities_count')
                ->label(__('crm.reports.customer.columns.won_opportunities_count')),
            ExportColumn::make('opportunities_agreed_amount_total')
                ->label(__('crm.reports.customer.columns.agreed_amount_total')),
            ExportColumn::make('last_follow_up_at')
                ->label(__('crm.reports.customer.columns.last_follow_up')),
            ExportColumn::make('created_at')
                ->label(__('crm.fields.created_at')),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        $user = auth()->user();

        abort_unless($user instanceof User && CrmReportAccess::canExportCustomerReports($user), 403);

        return $query;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('crm.reports.customer.export.completed', [
            'count' => number_format($export->successful_rows),
        ]);
    }
}
