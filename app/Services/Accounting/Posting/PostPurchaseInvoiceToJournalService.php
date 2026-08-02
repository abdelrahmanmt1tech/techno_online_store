<?php

namespace App\Services\Accounting\Posting;

use App\Enums\OperationType;
use App\Models\Tenant\Operation;
use App\Models\Tenant\PurchaseInvoice;
use App\Models\Tenant\Supplier;
use App\Services\Accounting\AccountingOperationWriter;
use App\Services\Accounting\ResolveFinancialPeriodService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Wave 2a simplified: Dr Inventory / Cr Supplier AP.
 * (GR already posts FIFO stock; GL inventory capitalizes at vendor invoice.)
 */
final class PostPurchaseInvoiceToJournalService
{
    public function __construct(
        private readonly AccountingOperationWriter $writer,
        private readonly ResolvePostingAccountsService $accounts,
        private readonly FindSystemOperationService $finder,
        private readonly ResolveFinancialPeriodService $periods,
    ) {}

    public function handle(PurchaseInvoice $invoice): ?Operation
    {
        if (! tenant_accounting_active()) {
            return null;
        }

        if (! $this->accounts->postingConfigured()) {
            return null;
        }

        $ref = 'PI-'.$invoice->id;
        if ($existing = $this->finder->find($invoice, $ref)) {
            return $existing;
        }

        $grand = round((float) $invoice->grand_total, 2);
        $tax = round((float) $invoice->tax_total, 2);
        $net = round($grand - $tax, 2);

        if ($grand <= 0) {
            return null;
        }

        $inventory = $this->accounts->inventory();
        $ap = $this->accounts->payableForSupplier((int) $invoice->supplier_id);
        $taxAccount = $tax > 0 ? $this->accounts->purchaseTaxReceivable() : null;

        $entries = [
            [
                'account_tree_id' => $inventory->id,
                'debit' => $net > 0 ? $net : $grand,
                'credit' => null,
                'notes' => $invoice->document_number,
            ],
            [
                'account_tree_id' => $ap->id,
                'debit' => null,
                'credit' => $grand,
                'notes' => $invoice->document_number,
            ],
        ];

        if ($tax > 0 && $taxAccount) {
            $entries[0]['debit'] = $net;
            $entries[] = [
                'account_tree_id' => $taxAccount->id,
                'debit' => $tax,
                'credit' => null,
                'notes' => $invoice->document_number,
            ];
        }

        $date = $invoice->invoice_date?->toDateString() ?? now()->toDateString();
        $period = $this->periods->resolveOpenForDate($date);
        $userId = Auth::guard('tenant')->id();

        return $this->writer->createOperationWithEntries([
            'financial_period_id' => $period?->id,
            'date' => $date,
            'comment' => __('dashboard.accounting_posting.purchase_invoice', [
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
            'linkable_type' => Supplier::class,
            'linkable_id' => $invoice->supplier_id,
            'service_type' => $invoice->getMorphClass(),
            'service_id' => $invoice->id,
        ], $entries);
    }
}
