<?php

namespace App\Http\Controllers\Crm\Reports;

use App\Models\TenantUser;
use App\Services\Crm\Reports\CrmReportFilters;
use App\Services\Crm\Reports\SourceReportQuery;
use App\Support\Crm\CrmReportAccess;
use Illuminate\Support\Collection;

class SourceReportPrintController extends CrmReportPrintController
{
    protected function reportTitle(): string
    {
        return __('crm.reports.source.title');
    }

    protected function viewName(): string
    {
        return 'filament.crm.reports.print-source';
    }

    protected function permissionCheck(TenantUser $user): bool
    {
        return CrmReportAccess::canViewSourceReports($user);
    }

    protected function rows(TenantUser $user, CrmReportFilters $filters): Collection
    {
        return SourceReportQuery::tableQuery($user, $filters)
            ->limit(self::MAX_ROWS)
            ->get();
    }

    protected function summary(TenantUser $user, CrmReportFilters $filters): array
    {
        return SourceReportQuery::summary($user, $filters);
    }

    protected function defaultDateBasis(): string
    {
        return 'clients.created_at';
    }
}
