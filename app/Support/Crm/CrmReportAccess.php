<?php

namespace App\Support\Crm;

use App\Models\TenantUser;

final class CrmReportAccess
{
    public static function canViewCustomerReports(TenantUser $user): bool
    {
        return $user->can('crm_customer_reports.view');
    }

    public static function canExportCustomerReports(TenantUser $user): bool
    {
        return $user->can('crm_customer_reports.export');
    }

    public static function canViewSourceReports(TenantUser $user): bool
    {
        return $user->can('crm_source_reports.view');
    }

    public static function canExportSourceReports(TenantUser $user): bool
    {
        return $user->can('crm_source_reports.export');
    }

    public static function canViewOpportunityReports(TenantUser $user): bool
    {
        return $user->can('crm_opportunity_reports.view');
    }

    public static function canExportOpportunityReports(TenantUser $user): bool
    {
        return $user->can('crm_opportunity_reports.export');
    }

    public static function canViewFollowUpReports(TenantUser $user): bool
    {
        return $user->can('crm_followup_reports.view');
    }

    public static function canExportFollowUpReports(TenantUser $user): bool
    {
        return $user->can('crm_followup_reports.export');
    }

    public static function canViewCampaignReports(TenantUser $user): bool
    {
        return $user->can('crm_campaign_reports.view');
    }

    public static function canExportCampaignReports(TenantUser $user): bool
    {
        return $user->can('crm_campaign_reports.export');
    }

    public static function canViewEmployeePerformanceReports(TenantUser $user): bool
    {
        return $user->can('crm_employee_performance_reports.view');
    }

    public static function canExportEmployeePerformanceReports(TenantUser $user): bool
    {
        return $user->can('crm_employee_performance_reports.export');
    }

    public static function canPrint(TenantUser $user): bool
    {
        return $user->can('crm_reports.print');
    }
}
