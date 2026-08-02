<?php

namespace App\Http\Controllers\Crm\Reports;

use App\Models\TenantUser;
use App\Services\Crm\Reports\CrmReportFilters;
use App\Services\Crm\Reports\CustomerReportQuery;
use App\Support\Crm\CrmReportAccess;
use Illuminate\Support\Collection;

class CustomerReportPrintController extends CrmReportPrintController
{
    protected function reportTitle(): string
    {
        return __('crm.reports.customer.title');
    }

    protected function viewName(): string
    {
        return 'filament.crm.reports.print-customer';
    }

    protected function permissionCheck(TenantUser $user): bool
    {
        return CrmReportAccess::canViewCustomerReports($user);
    }

    protected function rows(TenantUser $user, CrmReportFilters $filters): Collection
    {
        return CustomerReportQuery::tableQuery($user, $filters)
            ->limit(self::MAX_ROWS)
            ->get();
    }

    protected function summary(TenantUser $user, CrmReportFilters $filters): array
    {
        return CustomerReportQuery::summary($user, $filters);
    }

    protected function defaultDateBasis(): string
    {
        return 'created_at';
    }
}
