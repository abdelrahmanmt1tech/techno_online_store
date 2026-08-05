<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Models\Tenant\AccountTree;
use App\Models\Tenant\Branch;
use App\Models\Tenant\FinancialPeriod;
use App\Support\Modules\TenantModule;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BalanceSheet extends Page
{
    protected string $view = 'filament.pages.balance-sheet';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Scale;

    public function getTitle(): string
    {
        return __('dashboard.pages.balance_sheet.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.pages.balance_sheet.nav');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return tenant_module_enabled(TenantModule::Accounting) && ($user?->can('balance_sheet.view') ?? false);
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return tenant_module_enabled(TenantModule::Accounting) && ($user?->can('balance_sheet.view') ?? false);
    }



    /**
     * @var array<int, array{id:int, name:string}>
     */
    public array $branches = [];

    /**
     * @var array<int, array{
     *   account_name:string,
     *   subAccounts:array<int, array{
     *     account_name:string,
     *     branch_calcs:array<int, float|int>,
     *     row_total: float,
     *     debit_total: float,
     *     credit_total: float
     *   }>,
     *   totals: array{
     *     branch_totals:array<int, float>,
     *     row_total: float,
     *     debit_total: float,
     *     credit_total: float
     *   }
     * }>
     */
    public array $tableData = [];

    /**
     * @var array{branch_totals:array<int, float>, row_total: float, debit_total: float, credit_total: float}
     */
    public array $grandTotals = [
        'branch_totals' => [],
        'row_total' => 0.0,
        'debit_total' => 0.0,
        'credit_total' => 0.0,
    ];

    public ?int $financialPeriodId = null;

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public function mount(): void
    {
        $this->financialPeriodId = request()->integer('financial_period_id') ?: null;
        $this->fromDate = request()->query('from_date') ?: null;
        $this->toDate = request()->query('to_date') ?: null;
        $this->loadTableData();
    }

    protected function loadTableData(): void
    {
        [$fromDate, $toDate] = $this->resolveRange();
        $branches = Branch::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->branches = $branches->map(fn (Branch $branch): array => [
            'id' => $branch->id,
            'name' => (string) $branch->name,
        ])->all();

        $tableData = [];
        $grandBranchTotals = [];
        $grandRowTotal = 0.0;
        $grandDebitTotal = 0.0;
        $grandCreditTotal = 0.0;

        $accountTrees = AccountTree::query()
            ->whereNull('parent_id')
            ->with("subAccounts")
            ->whereIn('id' , [1,2,3])
            ->get();

        foreach ($accountTrees as $accountTree) {
            $item = [];
            $item['account_name'] = $accountTree->account_name;
            $subAccountTreeLiest = [];

            $sectionBranchTotals = [];
            $sectionRowTotal = 0.0;
            $sectionDebitTotal = 0.0;
            $sectionCreditTotal = 0.0;

            foreach ($accountTree->subAccounts as $subAccountTree) {
                $branchCalcs = [];
                $rowTotal = 0.0;
                $rowDebitTotal = 0.0;
                $rowCreditTotal = 0.0;

                foreach ($branches as $branch) {
                    $totals = $subAccountTree->accountTotalsDebitCreditForBranchAndDateRange(
                        (int) $branch->id,
                        $fromDate,
                        $toDate,
                    );
                    $netAbs = (float) ($totals['net_abs'] ?? 0);

                    $branchCalcs[$branch->id] = $netAbs;
                    $rowTotal += $netAbs;
                    $rowDebitTotal += (float) ($totals['debit'] ?? 0);
                    $rowCreditTotal += (float) ($totals['credit'] ?? 0);

                    $sectionBranchTotals[$branch->id] = ($sectionBranchTotals[$branch->id] ?? 0) + $netAbs;
                    $grandBranchTotals[$branch->id] = ($grandBranchTotals[$branch->id] ?? 0) + $netAbs;
                }

                $sectionRowTotal += $rowTotal;
                $sectionDebitTotal += $rowDebitTotal;
                $sectionCreditTotal += $rowCreditTotal;

                $grandRowTotal += $rowTotal;
                $grandDebitTotal += $rowDebitTotal;
                $grandCreditTotal += $rowCreditTotal;

                $subAccountTreeLiest[] = [
                    'branch_calcs' => $branchCalcs,
                    'account_name' => $subAccountTree->account_name,
                    'row_total' => $rowTotal,
                    'debit_total' => $rowDebitTotal,
                    'credit_total' => $rowCreditTotal,
                ];
            }
            $item['subAccounts'] = $subAccountTreeLiest;
            $item['totals'] = [
                'branch_totals' => $sectionBranchTotals,
                'row_total' => $sectionRowTotal,
                'debit_total' => $sectionDebitTotal,
                'credit_total' => $sectionCreditTotal,
            ];
            $tableData[] = $item;
        }

        $this->tableData = $tableData;
        $this->grandTotals = [
            'branch_totals' => $grandBranchTotals,
            'row_total' => $grandRowTotal,
            'debit_total' => $grandDebitTotal,
            'credit_total' => $grandCreditTotal,
        ];
    }

    protected function resolveRange(): array
    {
        if ($this->financialPeriodId) {
            $period = FinancialPeriod::query()->find($this->financialPeriodId);
            if ($period) {
                return [
                    $period->start_date->copy()->startOfDay(),
                    $period->end_date->copy()->endOfDay(),
                ];
            }
        }

        if ($this->fromDate && $this->toDate) {
            return [
                Carbon::parse($this->fromDate)->startOfDay(),
                Carbon::parse($this->toDate)->endOfDay(),
            ];
        }

        return [
            now()->startOfYear()->startOfDay(),
            now()->endOfDay(),
        ];
    }
}
