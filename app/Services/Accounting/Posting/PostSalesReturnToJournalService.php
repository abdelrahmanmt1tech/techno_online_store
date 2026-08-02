<?php

namespace App\Services\Accounting\Posting;

use App\Enums\OperationType;
use App\Models\Tenant\Client;
use App\Models\Tenant\Operation;
use App\Models\Tenant\SaleItem;
use App\Models\Tenant\SalesReturn;
use App\Services\Accounting\AccountingOperationWriter;
use App\Services\Accounting\ResolveFinancialPeriodService;
use App\Support\Erp\Decimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

final class PostSalesReturnToJournalService
{
    public function __construct(
        private readonly AccountingOperationWriter $writer,
        private readonly ResolvePostingAccountsService $accounts,
        private readonly FindSystemOperationService $finder,
        private readonly ResolveFinancialPeriodService $periods,
    ) {}

    /**
     * @return array{revenue: ?Operation, cogs: ?Operation}
     */
    public function handle(SalesReturn $salesReturn): array
    {
        if (! tenant_accounting_active()) {
            return ['revenue' => null, 'cogs' => null];
        }

        if (! $this->accounts->postingConfigured()) {
            return ['revenue' => null, 'cogs' => null];
        }

        $salesReturn->loadMissing(['items.saleItem', 'sale']);

        return [
            'revenue' => $this->postRevenueReverse($salesReturn),
            'cogs' => $this->postCogsReverse($salesReturn),
        ];
    }

    private function postRevenueReverse(SalesReturn $ret): ?Operation
    {
        $ref = 'SR-'.$ret->id.'-REV';
        if ($existing = $this->finder->find($ret, $ref)) {
            return $existing;
        }

        $net = '0';
        foreach ($ret->items as $item) {
            /** @var SaleItem|null $saleItem */
            $saleItem = $item->saleItem;
            if (! $saleItem) {
                continue;
            }
            $lineNet = Decimal::money(Decimal::mul((string) $item->quantity, (string) $saleItem->unit_price));
            $net = Decimal::add($net, $lineNet, 2);
        }

        $amount = round((float) Decimal::money($net), 2);
        if ($amount <= 0) {
            return null;
        }

        $returns = $this->accounts->salesReturns();
        $ar = $this->accounts->receivableForCustomer($ret->sale?->customer_id ? (int) $ret->sale->customer_id : null);
        $date = $ret->return_date?->toDateString() ?? now()->toDateString();
        $period = $this->periods->resolveOpenForDate($date);
        $userId = Auth::guard('tenant')->id();
        $customerId = $ret->sale?->customer_id;

        return $this->writer->createOperationWithEntries([
            'financial_period_id' => $period?->id,
            'date' => $date,
            'comment' => __('dashboard.accounting_posting.sales_return', [
                'number' => $ret->document_number,
            ]),
            'reference_no' => $ref,
            'settlement' => true,
            'status' => true,
            'operation_type' => OperationType::NORMAL,
            'is_posted' => true,
            'posted_at' => Carbon::now(),
            'posted_by' => $userId,
            'is_locked' => true,
            'locked_at' => Carbon::now(),
            'locked_by' => $userId,
            'is_system_generated' => true,
            'linkable_type' => $customerId ? Client::class : null,
            'linkable_id' => $customerId,
            'service_type' => $ret->getMorphClass(),
            'service_id' => $ret->id,
        ], [
            [
                'account_tree_id' => $returns->id,
                'debit' => $amount,
                'credit' => null,
                'notes' => $ret->document_number,
            ],
            [
                'account_tree_id' => $ar->id,
                'debit' => null,
                'credit' => $amount,
                'notes' => $ret->document_number,
            ],
        ]);
    }

    private function postCogsReverse(SalesReturn $ret): ?Operation
    {
        $ref = 'SR-'.$ret->id.'-COGS';
        if ($existing = $this->finder->find($ret, $ref)) {
            return $existing;
        }

        $cost = '0';
        foreach ($ret->items as $item) {
            /** @var SaleItem|null $saleItem */
            $saleItem = $item->saleItem;
            if (! $saleItem || ! Decimal::isPositive((string) $saleItem->quantity)) {
                continue;
            }
            $ratio = Decimal::div((string) $item->quantity, (string) $saleItem->quantity);
            $lineCost = Decimal::mul((string) ($saleItem->cost_total ?? 0), $ratio, 4);
            $cost = Decimal::add($cost, $lineCost, 4);
        }

        $costMoney = round((float) Decimal::money($cost), 2);
        if ($costMoney <= 0) {
            return null;
        }

        $cogs = $this->accounts->cogs();
        $inventory = $this->accounts->inventory();
        $date = $ret->return_date?->toDateString() ?? now()->toDateString();
        $period = $this->periods->resolveOpenForDate($date);
        $userId = Auth::guard('tenant')->id();
        $customerId = $ret->sale?->customer_id;

        return $this->writer->createOperationWithEntries([
            'financial_period_id' => $period?->id,
            'date' => $date,
            'comment' => __('dashboard.accounting_posting.sales_return_cogs', [
                'number' => $ret->document_number,
            ]),
            'reference_no' => $ref,
            'settlement' => true,
            'status' => true,
            'operation_type' => OperationType::NORMAL,
            'is_posted' => true,
            'posted_at' => Carbon::now(),
            'posted_by' => $userId,
            'is_locked' => true,
            'locked_at' => Carbon::now(),
            'locked_by' => $userId,
            'is_system_generated' => true,
            'linkable_type' => $customerId ? Client::class : null,
            'linkable_id' => $customerId,
            'service_type' => $ret->getMorphClass(),
            'service_id' => $ret->id,
        ], [
            [
                'account_tree_id' => $inventory->id,
                'debit' => $costMoney,
                'credit' => null,
                'notes' => $ret->document_number,
            ],
            [
                'account_tree_id' => $cogs->id,
                'debit' => null,
                'credit' => $costMoney,
                'notes' => $ret->document_number,
            ],
        ]);
    }
}
