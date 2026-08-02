<?php

namespace App\Support\Crm;

use App\Models\TenantUser;

final class CrmReportAccess
{
    public static function canViewCustomerReports(User $user): bool
    {
        return $user->can('crm_customer_reports.view');
    }

    public static function canExportCustomerReports(User $user): bool
    {
        return $user->can('crm_customer_reports.export');
    }

    public static function canViewSourceReports(User $user): bool
    {
        return $user->can('crm_source_reports.view');
    }

    public static function canExportSourceReports(User $user): bool
    {
        return $user->can('crm_source_reports.export');
    }

    public static function canViewOpportunityReports(User $user): bool
    {
        return $user->can('crm_opportunity_reports.view');
    }

    public static function canExportOpportunityReports(User $user): bool
    {
        return $user->can('crm_opportunity_reports.export');
    }

    public static function canViewFollowUpReports(User $user): bool
    {
        return $user->can('crm_followup_reports.view');
    }

    public static function canExportFollowUpReports(User $user): bool
    {
        return $user->can('crm_followup_reports.export');
    }

    public static function canViewCampaignReports(User $user): bool
    {
        return $user->can('crm_campaign_reports.view');
    }

    public static function canExportCampaignReports(User $user): bool
    {
        return $user->can('crm_campaign_reports.export');
    }

    public static function canViewEmployeePerformanceReports(User $user): bool
    {
        return $user->can('crm_employee_performance_reports.view');
    }

    public static function canExportEmployeePerformanceReports(User $user): bool
    {
        return $user->can('crm_employee_performance_reports.export');
    }

    public static function canPrint(User $user): bool
    {
        return $user->can('crm_reports.print');
    }
}
