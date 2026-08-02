<?php

namespace App\Http\Controllers\Crm\Reports;

use App\Models\TenantUser;
use App\Services\Crm\Reports\CampaignReportQuery;
use App\Services\Crm\Reports\CrmReportFilters;
use App\Support\Crm\CrmReportAccess;
use Illuminate\Support\Collection;

class CampaignReportPrintController extends CrmReportPrintController
{
    protected function reportTitle(): string
    {
        return __('crm.reports.campaign.title');
    }

    protected function viewName(): string
    {
        return 'filament.crm.reports.print-campaign';
    }

    protected function permissionCheck(TenantUser $user): bool
    {
        return CrmReportAccess::canViewCampaignReports($user);
    }

    protected function defaultDateBasis(): string
    {
        return 'start_date';
    }

    protected function rows(TenantUser $user, CrmReportFilters $filters): Collection
    {
        return CampaignReportQuery::tableQuery($user, $filters)
            ->limit(self::MAX_ROWS)
            ->get();
    }

    protected function summary(TenantUser $user, CrmReportFilters $filters): array
    {
        return CampaignReportQuery::summary($user, $filters);
    }
}
