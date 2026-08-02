<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Models\Tenant\AccountTree;
use App\Models\Tenant\FinancialPeriod;
use App\Exports\ProfitAndLossExport;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ProfitAndLoss extends Page
{
    protected string $view = 'filament.pages.profit-and-loss';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.pages.profit_and_loss.nav');
    }

    public function getTitle(): string
    {
        return __('dashboard.pages.profit_and_loss.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return $user?->can('profit_and_loss.view') ?? false;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user?->can('profit_and_loss.view') ?? false;
    }

    /**
     * @var array<int, array{
     *   account_id:int,
     *   account_name:string,
     *   subAccounts:array<int, array{account_name:string, current:float, current_percent:float, ytd:float, ytd_percent:float}>,
     *   totals: array{current:float, current_percent:float, ytd:float, ytd_percent:float}
     * }>
     */
    public array $tableData = [];

    /**
     * @var array{current:float, ytd:float}
     */
    public array $salesTotals = [
        'current' => 0.0,
        'ytd' => 0.0,
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
        [$currentFrom, $currentTo, $ytdFrom, $ytdTo] = $this->resolveRanges();

        $mainIds = [4, 5];

        $accountTrees = AccountTree::query()
            ->whereNull('parent_id')
            ->whereIn('id', $mainIds)
            ->with('subAccounts')
            ->get();

        // Sales totals (used as denominator for %)
        $salesTree = $accountTrees->firstWhere('id', 4);
        if ($salesTree) {
            $salesCurrent = $salesTree->accountTotalsDebitCreditForDateRange($currentFrom, $currentTo);
            $salesYtd = $salesTree->accountTotalsDebitCreditForDateRange($ytdFrom, $ytdTo);
            $this->salesTotals = [
                'current' => (float) ($salesCurrent['net_abs'] ?? 0),
                'ytd' => (float) ($salesYtd['net_abs'] ?? 0),
            ];
        }

        $salesCurrentDenominator = (float) ($this->salesTotals['current'] ?? 0);
        $salesYtdDenominator = (float) ($this->salesTotals['ytd'] ?? 0);

        $tableData = [];

        foreach ($accountTrees as $accountTree) {
            $rows = [];
            $sectionCurrentTotal = 0.0;
            $sectionYtdTotal = 0.0;

            foreach ($accountTree->subAccounts as $subAccountTree) {
                $current = $subAccountTree->accountTotalsDebitCreditForDateRange($currentFrom, $currentTo);
                $ytd = $subAccountTree->accountTotalsDebitCreditForDateRange($ytdFrom, $ytdTo);

                $currentAmount = (float) ($current['net_abs'] ?? 0);
                $ytdAmount = (float) ($ytd['net_abs'] ?? 0);

                $sectionCurrentTotal += $currentAmount;
                $sectionYtdTotal += $ytdAmount;

                $rows[] = [
                    'account_name' => (string) ($subAccountTree->account_name ?? '-'),
                    'current' => $currentAmount,
                    'current_percent' => $salesCurrentDenominator > 0 ? ($currentAmount / $salesCurrentDenominator) * 100 : 0.0,
                    'ytd' => $ytdAmount,
                    'ytd_percent' => $salesYtdDenominator > 0 ? ($ytdAmount / $salesYtdDenominator) * 100 : 0.0,
                ];
            }

            $tableData[] = [
                'account_id' => (int) $accountTree->id,
                'account_name' => (string) ($accountTree->account_name ?? '-'),
                'subAccounts' => $rows,
                'totals' => [
                    'current' => $sectionCurrentTotal,
                    'current_percent' => $salesCurrentDenominator > 0 ? ($sectionCurrentTotal / $salesCurrentDenominator) * 100 : 0.0,
                    'ytd' => $sectionYtdTotal,
                    'ytd_percent' => $salesYtdDenominator > 0 ? ($sectionYtdTotal / $salesYtdDenominator) * 100 : 0.0,
                ],
            ];
        }

        $this->tableData = $tableData;
    }

    protected function resolveRanges(): array
    {
        if ($this->financialPeriodId) {
            $period = FinancialPeriod::query()->find($this->financialPeriodId);
            if ($period) {
                $currentFrom = $period->start_date->copy()->startOfDay();
                $currentTo = $period->end_date->copy()->endOfDay();
                $ytdFrom = $period->end_date->copy()->startOfYear()->startOfDay();
                $ytdTo = $period->end_date->copy()->endOfDay();

                return [$currentFrom, $currentTo, $ytdFrom, $ytdTo];
            }
        }

        if ($this->fromDate && $this->toDate) {
            $currentFrom = Carbon::parse($this->fromDate)->startOfDay();
            $currentTo = Carbon::parse($this->toDate)->endOfDay();
            $ytdFrom = $currentTo->copy()->startOfYear()->startOfDay();
            $ytdTo = $currentTo->copy()->endOfDay();

            return [$currentFrom, $currentTo, $ytdFrom, $ytdTo];
        }

        $now = now();

        return [
            $now->copy()->startOfMonth()->startOfDay(),
            $now->copy()->endOfMonth()->endOfDay(),
            $now->copy()->startOfYear()->startOfDay(),
            $now->copy()->endOfDay(),
        ];
    }

    public function exportExcel(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(
            new ProfitAndLossExport($this->tableData, $this->salesTotals),
            sprintf('profit-and-loss-%s.xlsx', now()->format('Y-m-d-H-i-s')),
        );
    }
}

