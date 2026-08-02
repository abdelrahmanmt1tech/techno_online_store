<?php

namespace App\Http\Controllers\Crm\Reports;

use App\Models\TenantUser;
use App\Services\Crm\Reports\CrmReportFilters;
use App\Services\Crm\Reports\OpportunityReportQuery;
use App\Support\Crm\CrmReportAccess;
use Illuminate\Support\Collection;

class OpportunityReportPrintController extends CrmReportPrintController
{
    protected function reportTitle(): string
    {
        return __('crm.reports.opportunity.title');
    }

    protected function viewName(): string
    {
        return 'filament.crm.reports.print-opportunity';
    }

    protected function permissionCheck(TenantUser $user): bool
    {
        return CrmReportAccess::canViewOpportunityReports($user);
    }

    protected function rows(TenantUser $user, CrmReportFilters $filters): Collection
    {
        return OpportunityReportQuery::tableQuery($user, $filters)
            ->limit(self::MAX_ROWS)
            ->get();
    }

    protected function summary(TenantUser $user, CrmReportFilters $filters): array
    {
        return OpportunityReportQuery::summary($user, $filters);
    }
}
