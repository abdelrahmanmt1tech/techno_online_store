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
use App\Support\Modules\TenantModule;
use App\Support\Modules\TenantModuleGate;
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
     * modules: any of the listed sellable modules must be enabled.
     * omit modules (or empty) = always eligible for module gate (still subject to resource canAccess).
     *
     * @return array<string, array<int, array<string, mixed>|string>>
     */
    private function groupEntries(): array
    {
        $commerce = [TenantModule::Store, TenantModule::Pos];
        $store = [TenantModule::Store];
        $pos = [TenantModule::Pos];
        $crm = [TenantModule::Crm];
        $accounting = [TenantModule::Accounting];
        $supplier = [TenantModule::Store, TenantModule::Pos, TenantModule::Crm];

        return [
            'sales_pos' => [
                ['type' => 'manual', 'key' => 'pos_terminal', 'modules' => $pos],
                ['class' => CashierSessionResource::class, 'modules' => $pos],
                ['class' => SalesInvoiceResource::class, 'modules' => $commerce],
                ['class' => SalesReturnResource::class, 'modules' => $commerce],
                ['class' => CashDrawerResource::class, 'modules' => $pos],
                ['class' => PosPaymentMethodResource::class, 'modules' => $pos],
                ['class' => SaleResource::class, 'modules' => $commerce],
                ['class' => InvoicePaymentResource::class, 'modules' => $commerce],
                ['class' => PosRegisterResource::class, 'modules' => $pos],
                ['class' => PosSettingResource::class, 'modules' => $pos],
                ['class' => CashMovementResource::class, 'modules' => $pos],
            ],
            'purchases_suppliers' => [
                ['class' => PurchaseOrderResource::class, 'modules' => $commerce],
                ['class' => GoodsReceiptResource::class, 'modules' => $commerce],
                ['class' => PurchaseInvoiceResource::class, 'modules' => $commerce],
                ['class' => PurchaseReturnResource::class, 'modules' => $commerce],
                ['class' => SupplierResource::class, 'label' => 'tenant_navigation.items.supplier_directory', 'modules' => $supplier],
            ],
            'inventory_management' => [
                ['class' => InventoryItemResource::class, 'label' => 'tenant_navigation.items.inventory_and_products', 'modules' => $commerce],
                ['class' => ProductResource::class, 'modules' => $commerce],
                ['class' => CategoryResource::class, 'modules' => $commerce],
                ['class' => StockReceiptResource::class, 'modules' => $commerce],
                ['class' => StockIssueResource::class, 'modules' => $commerce],
                ['class' => StockTransferResource::class, 'modules' => $commerce],
                ['class' => StockAdjustmentResource::class, 'label' => 'tenant_navigation.items.stock_adjustments', 'modules' => $commerce],
                ['class' => StockDamageResource::class, 'label' => 'tenant_navigation.items.stock_damages', 'modules' => $commerce],
                ['class' => StockMovementResource::class, 'modules' => $commerce],
                ['class' => StockBalanceResource::class, 'modules' => $commerce],
                ['class' => WarehouseResource::class, 'modules' => $commerce],
            ],
            'finance_accounting' => [
                ['class' => ProfitAndLoss::class, 'modules' => $accounting],
                ['class' => TrialBalance::class, 'modules' => $accounting],
                ['class' => OperationResource::class, 'label' => 'tenant_navigation.items.accounting_entries', 'modules' => $accounting],
                ['class' => AccountTreeResource::class, 'modules' => $accounting],
                ['class' => AccountsCenterResource::class, 'label' => 'tenant_navigation.items.cost_centers', 'modules' => $accounting],
                ['class' => PartyAccountStatement::class, 'label' => 'tenant_navigation.items.party_account_statement', 'modules' => $accounting],
                ['class' => GeneralLedger::class, 'modules' => $accounting],
                ['class' => FinancialPeriodResource::class, 'modules' => $accounting],
                ['class' => BalanceSheet::class, 'modules' => $accounting],
                ['class' => AccountingSettings::class, 'modules' => $accounting],
                ['class' => OpeningEntriesReport::class, 'modules' => $accounting],
                ['class' => PeriodBalancesSnapshotReport::class, 'modules' => $accounting],
                ['class' => AccountsCentersReport::class, 'modules' => $accounting],
                ['class' => AccountsCenterDetailsReport::class, 'modules' => $accounting],
                ['class' => AccountTreeCleanupPage::class, 'modules' => $accounting],
            ],
            'crm_marketing' => [
                ['class' => ClientResource::class, 'label' => 'tenant_navigation.items.clients_and_leads', 'modules' => $crm],
                ['class' => LeadClients::class, 'modules' => $crm],
                ['class' => OpportunityResource::class, 'modules' => $crm],
                ['class' => OpportunityFollowUpResource::class, 'modules' => $crm],
                ['class' => CampaignResource::class, 'modules' => $crm],
                ['class' => WhatsAppInboxPage::class, 'modules' => $crm],
                ['class' => ConnectWhatsAppPage::class, 'modules' => $crm],
                ['class' => WhatsAppNumberResource::class, 'modules' => $crm],
                ['class' => WhatsAppTemplateResource::class, 'modules' => $crm],
                ['class' => WhatsAppContactResource::class, 'modules' => $crm],
                ['class' => MessengerInboxPage::class, 'modules' => $crm],
                ['class' => ConnectMessengerPage::class, 'modules' => $crm],
                ['class' => MessengerPageResource::class, 'modules' => $crm],
                ['class' => LeadSourceResource::class, 'modules' => $crm],
                ['class' => OpportunityStageResource::class, 'modules' => $crm],
                ['class' => FollowUpTypeResource::class, 'modules' => $crm],
                ['class' => FollowUpStatusResource::class, 'modules' => $crm],
                ['class' => CustomerReportsPage::class, 'modules' => $crm],
                ['class' => SourceReportsPage::class, 'modules' => $crm],
                ['class' => OpportunityReportsPage::class, 'modules' => $crm],
                ['class' => FollowUpReportsPage::class, 'modules' => $crm],
                ['class' => CampaignReportsPage::class, 'modules' => $crm],
                ['class' => EmployeePerformanceReportsPage::class, 'modules' => $crm],
            ],
            'ecommerce_website' => [
                ['class' => OrderResource::class, 'modules' => $store],
                ['class' => CustomerResource::class, 'modules' => $store],
                ['class' => CouponResource::class, 'modules' => $store],
                ['class' => ReviewResource::class, 'label' => 'tenant_navigation.items.review_ratings', 'modules' => $store],
                ['class' => HomeSectionBuilder::class, 'modules' => $store],
                ['class' => PageResource::class, 'modules' => $store],
                ['class' => BrowseThemesPage::class, 'modules' => $store],
                ['class' => ContactUsSettings::class, 'modules' => $store],
                ['class' => FooterSettings::class, 'modules' => $store],
                ['class' => GovernorateResource::class, 'label' => 'tenant_navigation.items.governorates_and_regions', 'modules' => $store],
                ['class' => ContactResource::class, 'modules' => $store],
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
                ['class' => MyCommissions::class, 'modules' => $crm],
                ['class' => OpportunityCommissionResource::class, 'modules' => $crm],
                ['class' => CommissionPaymentCycleResource::class, 'modules' => $crm],
                HrJobTitleResource::class,
                HrSettingResource::class,
            ],
            'settings_admin' => [
                GeneralSettings::class,
                RoleResource::class,
                TenantUserResource::class,
                ['class' => InvoicePrintSettingResource::class, 'label' => 'tenant_navigation.items.invoice_printing_and_webhooks', 'modules' => $commerce],
                ['class' => WhatsAppWebhookEventResource::class, 'modules' => $crm],
                ['class' => WhatsAppApiRequestResource::class, 'modules' => $crm],
                ['class' => MessengerWebhookEventResource::class, 'modules' => $crm],
                ['class' => MessengerApiRequestResource::class, 'modules' => $crm],
                ['class' => UnitOfMeasureResource::class, 'modules' => $commerce],
                ['class' => BrandResource::class, 'modules' => $commerce],
                CodeSettings::class,
                BranchResource::class,
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>|string>  $entries
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

            if (! $this->modulesAllow($entry['modules'] ?? [])) {
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
     * @param  list<TenantModule|string>  $modules
     */
    private function modulesAllow(array $modules): bool
    {
        if ($modules === []) {
            return true;
        }

        return TenantModuleGate::anyEnabled(...$modules);
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
        return __('tenant_navigation.groups.'.$groupKey);
    }
}
