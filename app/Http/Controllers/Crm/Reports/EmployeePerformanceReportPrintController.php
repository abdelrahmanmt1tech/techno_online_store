<?php

namespace App\Http\Controllers\Crm\Reports;

use App\Models\TenantUser;
use App\Services\Crm\Reports\CrmReportFilters;
use App\Services\Crm\Reports\EmployeePerformanceReportQuery;
use App\Support\Crm\CrmReportAccess;
use Illuminate\Support\Collection;

class EmployeePerformanceReportPrintController extends CrmReportPrintController
{
    protected function reportTitle(): string
    {
        return __('crm.reports.employee.title');
    }

    protected function viewName(): string
    {
        return 'filament.crm.reports.print-employee';
    }

    protected function permissionCheck(TenantUser $user): bool
    {
        return CrmReportAccess::canViewEmployeePerformanceReports($user);
    }

    protected function rows(TenantUser $user, CrmReportFilters $filters): Collection
    {
        return EmployeePerformanceReportQuery::tableQuery($user, $filters)
            ->limit(self::MAX_ROWS)
            ->get();
    }

    protected function summary(TenantUser $user, CrmReportFilters $filters): array
    {
        return EmployeePerformanceReportQuery::summary($user, $filters);
    }
}
