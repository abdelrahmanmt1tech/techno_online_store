<?php

namespace App\Http\Controllers\Crm\Reports;

use App\Models\TenantUser;
use App\Services\Crm\Reports\CrmReportFilters;
use App\Services\Crm\Reports\FollowUpReportQuery;
use App\Support\Crm\CrmReportAccess;
use Illuminate\Support\Collection;

class FollowUpReportPrintController extends CrmReportPrintController
{
    protected function reportTitle(): string
    {
        return __('crm.reports.followup.title');
    }

    protected function viewName(): string
    {
        return 'filament.crm.reports.print-follow-up';
    }

    protected function permissionCheck(TenantUser $user): bool
    {
        return CrmReportAccess::canViewFollowUpReports($user);
    }

    protected function defaultDateBasis(): string
    {
        return 'scheduled_at';
    }

    protected function rows(TenantUser $user, CrmReportFilters $filters): Collection
    {
        return FollowUpReportQuery::tableQuery($user, $filters)
            ->limit(self::MAX_ROWS)
            ->get();
    }

    protected function summary(TenantUser $user, CrmReportFilters $filters): array
    {
        return FollowUpReportQuery::summary($user, $filters);
    }
}
