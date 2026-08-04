<?php

namespace App\Support\Filament;

use App\Filament\Crm\Pages\LeadClients;
use App\Filament\Crm\Pages\MyCommissions;
use App\Filament\Crm\Pages\Reports\CampaignReportsPage;
use App\Filament\Crm\Pages\Reports\CustomerReportsPage;
use App\Filament\Crm\Pages\Reports\EmployeePerformanceReportsPage;
use App\Filament\Crm\Pages\Reports\FollowUpReportsPage;
use App\Filament\Crm\Pages\Reports\OpportunityReportsPage;
use App\Filament\Crm\Pages\Reports\SourceReportsPage;
use App\Filament\Crm\Resources\Campaigns\CampaignResource;
use App\Filament\Crm\Resources\CommissionPaymentCycles\CommissionPaymentCycleResource;
use App\Filament\Crm\Resources\FollowUpStatuses\FollowUpStatusResource;
use App\Filament\Crm\Resources\FollowUpTypes\FollowUpTypeResource;
use App\Filament\Crm\Resources\Opportunities\OpportunityResource;
use App\Filament\Crm\Resources\OpportunityCommissions\OpportunityCommissionResource;
use App\Filament\Crm\Resources\OpportunityFollowUps\OpportunityFollowUpResource;
use App\Filament\Crm\Resources\OpportunityStages\OpportunityStageResource;
use App\Filament\Tenant\Pages\Accounting\AccountTreeCleanupPage;
use App\Filament\Tenant\Pages\Accounting\AccountingSettings;
use App\Filament\Tenant\Pages\Accounting\AccountsCenterDetailsReport;
use App\Filament\Tenant\Pages\Accounting\AccountsCentersReport;
use App\Filament\Tenant\Pages\Accounting\BalanceSheet;
use App\Filament\Tenant\Pages\Accounting\GeneralLedger;
use App\Filament\Tenant\Pages\Accounting\OpeningEntriesReport;
use App\Filament\Tenant\Pages\Accounting\PartyAccountStatement;
use App\Filament\Tenant\Pages\Accounting\PeriodBalancesSnapshotReport;
use App\Filament\Tenant\Pages\Accounting\ProfitAndLoss;
use App\Filament\Tenant\Pages\Accounting\TrialBalance;
use App\Filament\Tenant\Pages\BrowseThemesPage;
use App\Filament\Tenant\Pages\CodeSettings;
use App\Filament\Tenant\Pages\ConnectMessengerPage;
use App\Filament\Tenant\Pages\ConnectWhatsAppPage;
use App\Filament\Tenant\Pages\ContactUsSettings;
use App\Filament\Tenant\Pages\Dashboard;
use App\Filament\Tenant\Pages\FooterSettings;
use App\Filament\Tenant\Pages\GeneralSettings;
use App\Filament\Tenant\Pages\HomeSectionBuilder;
use App\Filament\Tenant\Pages\HrAttendanceSummaryPage;
use App\Filament\Tenant\Pages\HrPayrollSummaryPage;
use App\Filament\Tenant\Pages\MessengerInboxPage;
use App\Filament\Tenant\Pages\WhatsAppInboxPage;
use App\Filament\Tenant\Resources\AccountsCenterResource;
use App\Filament\Tenant\Resources\AccountTrees\AccountTreeResource;
use App\Filament\Tenant\Resources\Branches\BranchResource;
use App\Filament\Tenant\Resources\Brands\BrandResource;
use App\Filament\Tenant\Resources\CashDrawers\CashDrawerResource;
use App\Filament\Tenant\Resources\CashierSessions\CashierSessionResource;
use App\Filament\Tenant\Resources\CashMovements\CashMovementResource;
use App\Filament\Tenant\Resources\Categories\CategoryResource;
use App\Filament\Tenant\Resources\Clients\ClientResource;
use App\Filament\Tenant\Resources\Contacts\ContactResource;
use App\Filament\Tenant\Resources\Coupons\CouponResource;
use App\Filament\Tenant\Resources\Customers\CustomerResource;
use App\Filament\Tenant\Resources\FinancialPeriods\FinancialPeriodResource;
use App\Filament\Tenant\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Filament\Tenant\Resources\Governorates\GovernorateResource;
use App\Filament\Tenant\Resources\HrAttendanceLocations\HrAttendanceLocationResource;
use App\Filament\Tenant\Resources\HrAttendanceRecords\HrAttendanceRecordResource;
use App\Filament\Tenant\Resources\HrAttendanceSchedules\HrAttendanceScheduleResource;
use App\Filament\Tenant\Resources\HrDepartments\HrDepartmentResource;
use App\Filament\Tenant\Resources\HrEmployees\HrEmployeeResource;
use App\Filament\Tenant\Resources\HrJobTitles\HrJobTitleResource;
use App\Filament\Tenant\Resources\HrPayrollPeriods\HrPayrollPeriodResource;
use App\Filament\Tenant\Resources\HrSettings\HrSettingResource;
use App\Filament\Tenant\Resources\InventoryItems\InventoryItemResource;
use App\Filament\Tenant\Resources\InvoicePayments\InvoicePaymentResource;
use App\Filament\Tenant\Resources\InvoicePrintSettings\InvoicePrintSettingResource;
use App\Filament\Tenant\Resources\LeadSources\LeadSourceResource;
use App\Filament\Tenant\Resources\MessengerApiRequests\MessengerApiRequestResource;
use App\Filament\Tenant\Resources\MessengerPages\MessengerPageResource;
use App\Filament\Tenant\Resources\MessengerWebhookEvents\MessengerWebhookEventResource;
use App\Filament\Tenant\Resources\Operations\OperationResource;
use App\Filament\Tenant\Resources\Orders\OrderResource;
use App\Filament\Tenant\Resources\Pages\PageResource;
use App\Filament\Tenant\Resources\PosPaymentMethods\PosPaymentMethodResource;
use App\Filament\Tenant\Resources\PosRegisters\PosRegisterResource;
use App\Filament\Tenant\Resources\PosSettings\PosSettingResource;
use App\Filament\Tenant\Resources\Products\ProductResource;
use App\Filament\Tenant\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Filament\Tenant\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Tenant\Resources\PurchaseReturns\PurchaseReturnResource;
use App\Filament\Tenant\Resources\Reviews\ReviewResource;
use App\Filament\Tenant\Resources\Roles\RoleResource;
use App\Filament\Tenant\Resources\Sales\SaleResource;
use App\Filament\Tenant\Resources\SalesInvoices\SalesInvoiceResource;
use App\Filament\Tenant\Resources\SalesReturns\SalesReturnResource;
use App\Filament\Tenant\Resources\StockBalances\StockBalanceResource;
use App\Filament\Tenant\Resources\StockMovements\StockMovementResource;
use App\Filament\Tenant\Resources\StockTransactions\StockAdjustmentResource;
use App\Filament\Tenant\Resources\StockTransactions\StockDamageResource;
use App\Filament\Tenant\Resources\StockTransactions\StockIssueResource;
use App\Filament\Tenant\Resources\StockTransactions\StockReceiptResource;
use App\Filament\Tenant\Resources\StockTransactions\StockTransferResource;
use App\Filament\Tenant\Resources\Suppliers\SupplierResource;
use App\Filament\Tenant\Resources\TenantUsers\TenantUserResource;
use App\Filament\Tenant\Resources\UnitsOfMeasure\UnitOfMeasureResource;
use App\Filament\Tenant\Resources\Warehouses\WarehouseResource;
use App\Filament\Tenant\Resources\WhatsAppApiRequests\WhatsAppApiRequestResource;
use App\Filament\Tenant\Resources\WhatsAppContacts\WhatsAppContactResource;
use App\Filament\Tenant\Resources\WhatsAppNumbers\WhatsAppNumberResource;
use App\Filament\Tenant\Resources\WhatsAppTemplates\WhatsAppTemplateResource;
use App\Filament\Tenant\Resources\WhatsAppWebhookEvents\WhatsAppWebhookEventResource;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use function Filament\Support\original_request;

class TenantNavigationBuilder
{
    public function build(NavigationBuilder $builder): NavigationBuilder
    {
        return $builder
            ->items($this->dashboardItems())
            ->groups($this->groups());
    }

    /**
     * @return array<int, NavigationItem>
     */
    private function dashboardItems(): array
    {
        return $this->prepareItems(Dashboard::class, null, 0);
    }

    /**
     * @return array<int, NavigationGroup>
     */
    private function groups(): array
    {
        $groups = [];

        foreach ($this->groupEntries() as $groupKey => $entries) {
            $items = $this->buildGroupItems($groupKey, $entries);

            if ($items === []) {
                continue;
            }

            $groups[] = NavigationGroup::make()
                ->label($this->groupLabel($groupKey))
                ->collapsed()
                ->items($items);
        }

        return $groups;
    }

    /**
     * @return array<string, array<int, array<string, string>|string>>
     */
    private function groupEntries(): array
    {
        return [
            'sales_pos' => [
                ['type' => 'manual', 'key' => 'pos_terminal'],
                CashierSessionResource::class,
                SalesInvoiceResource::class,
                SalesReturnResource::class,
                CashDrawerResource::class,
                PosPaymentMethodResource::class,
                SaleResource::class,
                InvoicePaymentResource::class,
                PosRegisterResource::class,
                PosSettingResource::class,
                CashMovementResource::class,
            ],
            'purchases_suppliers' => [
                PurchaseOrderResource::class,
                GoodsReceiptResource::class,
                PurchaseInvoiceResource::class,
                PurchaseReturnResource::class,
                ['class' => SupplierResource::class, 'label' => 'tenant_navigation.items.supplier_directory'],
            ],
            'inventory_management' => [
                ['class' => InventoryItemResource::class, 'label' => 'tenant_navigation.items.inventory_and_products'],
                ProductResource::class,
                CategoryResource::class,
                StockReceiptResource::class,
                StockIssueResource::class,
                StockTransferResource::class,
                ['class' => StockAdjustmentResource::class, 'label' => 'tenant_navigation.items.stock_adjustments'],
                ['class' => StockDamageResource::class, 'label' => 'tenant_navigation.items.stock_damages'],
                StockMovementResource::class,
                StockBalanceResource::class,
                WarehouseResource::class,
            ],
            'finance_accounting' => [
                ProfitAndLoss::class,
                TrialBalance::class,
                ['class' => OperationResource::class, 'label' => 'tenant_navigation.items.accounting_entries'],
                AccountTreeResource::class,
                ['class' => AccountsCenterResource::class, 'label' => 'tenant_navigation.items.cost_centers'],
                ['class' => PartyAccountStatement::class, 'label' => 'tenant_navigation.items.party_account_statement'],
                GeneralLedger::class,
                FinancialPeriodResource::class,
                BalanceSheet::class,
                AccountingSettings::class,
                OpeningEntriesReport::class,
                PeriodBalancesSnapshotReport::class,
                AccountsCentersReport::class,
                AccountsCenterDetailsReport::class,
                AccountTreeCleanupPage::class,
            ],
            'crm_marketing' => [
                ['class' => ClientResource::class, 'label' => 'tenant_navigation.items.clients_and_leads'],
                LeadClients::class,
                OpportunityResource::class,
                OpportunityFollowUpResource::class,
                CampaignResource::class,
                WhatsAppInboxPage::class,
                ConnectWhatsAppPage::class,
                WhatsAppNumberResource::class,
                WhatsAppTemplateResource::class,
                WhatsAppContactResource::class,
                MessengerInboxPage::class,
                ConnectMessengerPage::class,
                MessengerPageResource::class,
                LeadSourceResource::class,
                OpportunityStageResource::class,
                FollowUpTypeResource::class,
                FollowUpStatusResource::class,
                CustomerReportsPage::class,
                SourceReportsPage::class,
                OpportunityReportsPage::class,
                FollowUpReportsPage::class,
                CampaignReportsPage::class,
                EmployeePerformanceReportsPage::class,
            ],
            'ecommerce_website' => [
                OrderResource::class,
                CustomerResource::class,
                CouponResource::class,
                ['class' => ReviewResource::class, 'label' => 'tenant_navigation.items.review_ratings'],
                HomeSectionBuilder::class,
                PageResource::class,
                BrowseThemesPage::class,
                ContactUsSettings::class,
                FooterSettings::class,
                ['class' => GovernorateResource::class, 'label' => 'tenant_navigation.items.governorates_and_regions'],
                ContactResource::class,
            ],
            'human_resources' => [
                HrEmployeeResource::class,
                HrDepartmentResource::class,
                ['type' => 'manual', 'key' => 'hr_attendance'],
                HrAttendanceRecordResource::class,
                HrAttendanceScheduleResource::class,
                HrAttendanceLocationResource::class,
                HrAttendanceSummaryPage::class,
                HrPayrollPeriodResource::class,
                HrPayrollSummaryPage::class,
                MyCommissions::class,
                OpportunityCommissionResource::class,
                CommissionPaymentCycleResource::class,
                HrJobTitleResource::class,
                HrSettingResource::class,
            ],
            'settings_admin' => [
                GeneralSettings::class,
                RoleResource::class,
                TenantUserResource::class,
                ['class' => InvoicePrintSettingResource::class, 'label' => 'tenant_navigation.items.invoice_printing_and_webhooks'],
                WhatsAppWebhookEventResource::class,
                WhatsAppApiRequestResource::class,
                MessengerWebhookEventResource::class,
                MessengerApiRequestResource::class,
                UnitOfMeasureResource::class,
                BrandResource::class,
                CodeSettings::class,
                BranchResource::class,
            ],
        ];
    }

    /**
     * @param  array<int, array<string, string>|string>  $entries
     * @return array<int, NavigationItem>
     */
    private function buildGroupItems(string $groupKey, array $entries): array
    {
        $groupLabel = $this->groupLabel($groupKey);
        $items = [];
        $sort = 10;

        foreach ($entries as $entry) {
            if (is_string($entry)) {
                array_push($items, ...$this->prepareItems($entry, $groupLabel, $sort));
                $sort += 10;

                continue;
            }

            if (($entry['type'] ?? null) === 'manual') {
                $items[] = $this->manualItem($entry['key'], $groupLabel, $sort);
                $sort += 10;

                continue;
            }

            array_push(
                $items,
                ...$this->prepareItems($entry['class'], $groupLabel, $sort, $entry['label'] ?? null),
            );

            $sort += 10;
        }

        return $items;
    }

    /**
     * @return array<int, NavigationItem>
     */
    private function prepareItems(
        string $class,
        ?string $groupLabel,
        int $sort,
        ?string $labelKey = null,
    ): array {
        return array_map(
            function (NavigationItem $item) use ($groupLabel, $sort, $labelKey): NavigationItem {
                if ($groupLabel !== null) {
                    $item->group($groupLabel);
                }

                $item->sort($sort);

                if ($labelKey !== null) {
                    $item->label(__($labelKey));
                }

                return $item;
            },
            $class::getNavigationItems(),
        );
    }

    private function manualItem(string $key, string $groupLabel, int $sort): NavigationItem
    {
        return match ($key) {
            'pos_terminal' => NavigationItem::make('tenant-pos-terminal')
                ->label(__('tenant_navigation.items.pos_terminal'))
                ->icon('heroicon-o-calculator')
                ->group($groupLabel)
                ->sort($sort)
                ->url(fn (): string => url('/app/pos'))
                ->isActiveWhen(fn (): bool => original_request()->is('app/pos*')),
            'hr_attendance' => NavigationItem::make('tenant-hr-attendance')
                ->label(__('hr.nav.my_attendance'))
                ->icon('heroicon-o-finger-print')
                ->group($groupLabel)
                ->sort($sort)
                ->url(fn (): string => url('/app/hr/attendance'))
                ->isActiveWhen(fn (): bool => original_request()->is('app/hr/attendance*')),
        };
    }

    private function groupLabel(string $groupKey): string
    {
        return __('tenant_navigation.groups.' . $groupKey);
    }
}
