<?php

namespace App\Services\Accounting\Posting;

use App\Enums\OperationType;
use App\Models\Tenant\Client;
use App\Models\Tenant\Operation;
use App\Models\Tenant\SaleItem;
use App\Models\Tenant\SalesInvoice;
use App\Services\Accounting\AccountingOperationWriter;
use App\Services\Accounting\ResolveFinancialPeriodService;
use App\Support\Erp\Decimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

final class PostSalesInvoiceToJournalService
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
    public function handle(SalesInvoice $invoice): array
    {
        if (! tenant_accounting_active()) {
            return ['revenue' => null, 'cogs' => null];
        }

        if (! $this->accounts->postingConfigured()) {
            return ['revenue' => null, 'cogs' => null];
        }

        $invoice->loadMissing(['items.saleItem', 'sale', 'customer']);

        return [
            'revenue' => $this->postRevenue($invoice),
            'cogs' => $this->postCogs($invoice),
        ];
    }

    private function postRevenue(SalesInvoice $invoice): ?Operation
    {
        $ref = 'SI-'.$invoice->id.'-REV';
        if ($this->finder->find($invoice, $ref)) {
            return $this->finder->find($invoice, $ref);
        }

        $grand = round((float) $invoice->grand_total, 2);
        $tax = round((float) $invoice->tax_total, 2);
        $net = round((float) Decimal::sub($invoice->subtotal, $invoice->discount_total, 2), 2);

        if ($grand <= 0 && $net <= 0) {
            return null;
        }

        $ar = $this->accounts->receivableForCustomer($invoice->customer_id ? (int) $invoice->customer_id : null);
        $revenue = $this->accounts->salesRevenue();
        $taxAccount = $tax > 0 ? $this->accounts->salesTaxPayable() : null;

        $entries = [
            [
                'account_tree_id' => $ar->id,
                'debit' => $grand,
                'credit' => null,
                'notes' => $invoice->document_number,
            ],
            [
                'account_tree_id' => $revenue->id,
                'debit' => null,
                'credit' => $net,
                'notes' => $invoice->document_number,
            ],
        ];

        if ($tax > 0 && $taxAccount) {
            $entries[] = [
                'account_tree_id' => $taxAccount->id,
                'debit' => null,
                'credit' => $tax,
                'notes' => $invoice->document_number,
            ];
        } elseif ($tax > 0) {
            // No tax account configured — fold tax into revenue credit to keep balance.
            $entries[1]['credit'] = round($net + $tax, 2);
        }

        $date = $invoice->invoice_date?->toDateString() ?? now()->toDateString();
        $period = $this->periods->resolveOpenForDate($date);
        $userId = Auth::guard('tenant')->id();

        return $this->writer->createOperationWithEntries([
            'financial_period_id' => $period?->id,
            'date' => $date,
            'comment' => __('dashboard.accounting_posting.sales_invoice', [
                'number' => $invoice->document_number,
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
            'linkable_type' => $invoice->customer_id ? Client::class : null,
            'linkable_id' => $invoice->customer_id,
            'service_type' => $invoice->getMorphClass(),
            'service_id' => $invoice->id,
        ], $entries);
    }

    private function postCogs(SalesInvoice $invoice): ?Operation
    {
        $ref = 'SI-'.$invoice->id.'-COGS';
        if ($this->finder->find($invoice, $ref)) {
            return $this->finder->find($invoice, $ref);
        }

        $cost = '0';
        foreach ($invoice->items as $line) {
            /** @var SaleItem|null $saleItem */
            $saleItem = $line->saleItem;
            if (! $saleItem || ! Decimal::isPositive((string) $saleItem->quantity)) {
                continue;
            }
            $ratio = Decimal::div((string) $line->quantity, (string) $saleItem->quantity);
            $lineCost = Decimal::mul((string) ($saleItem->cost_total ?? 0), $ratio, 4);
            $cost = Decimal::add($cost, $lineCost, 4);
        }

        $costMoney = round((float) Decimal::money($cost), 2);
        if ($costMoney <= 0) {
            return null;
        }

        $cogs = $this->accounts->cogs();
        $inventory = $this->accounts->inventory();
        $date = $invoice->invoice_date?->toDateString() ?? now()->toDateString();
        $period = $this->periods->resolveOpenForDate($date);
        $userId = Auth::guard('tenant')->id();

        return $this->writer->createOperationWithEntries([
            'financial_period_id' => $period?->id,
            'date' => $date,
            'comment' => __('dashboard.accounting_posting.sales_invoice_cogs', [
                'number' => $invoice->document_number,
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
            'linkable_type' => $invoice->customer_id ? Client::class : null,
            'linkable_id' => $invoice->customer_id,
            'service_type' => $invoice->getMorphClass(),
            'service_id' => $invoice->id,
        ], [
            [
                'account_tree_id' => $cogs->id,
                'debit' => $costMoney,
                'credit' => null,
                'notes' => $invoice->document_number,
            ],
            [
                'account_tree_id' => $inventory->id,
                'debit' => null,
                'credit' => $costMoney,
                'notes' => $invoice->document_number,
            ],
        ]);
    }
}
