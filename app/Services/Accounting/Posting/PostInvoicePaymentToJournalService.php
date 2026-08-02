<?php

namespace App\Services\Accounting\Posting;

use App\Enums\Erp\InvoicePayableType;
use App\Enums\Erp\PaymentMethod;
use App\Enums\OperationType;
use App\Models\Tenant\Client;
use App\Models\Tenant\InvoicePayment;
use App\Models\Tenant\Operation;
use App\Models\Tenant\PurchaseInvoice;
use App\Models\Tenant\SalesInvoice;
use App\Models\Tenant\Supplier;
use App\Services\Accounting\AccountingOperationWriter;
use App\Services\Accounting\ResolveFinancialPeriodService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

final class PostInvoicePaymentToJournalService
{
    public function __construct(
        private readonly AccountingOperationWriter $writer,
        private readonly ResolvePostingAccountsService $accounts,
        private readonly FindSystemOperationService $finder,
        private readonly ResolveFinancialPeriodService $periods,
    ) {}

    public function handle(InvoicePayment $payment): ?Operation
    {
        if (! tenant_accounting_active()) {
            return null;
        }

        if (! $this->accounts->postingConfigured()) {
            return null;
        }

        $ref = 'PAY-'.$payment->id;
        if ($existing = $this->finder->find($payment, $ref)) {
            return $existing;
        }

        $amount = round((float) $payment->amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $method = $payment->payment_method instanceof PaymentMethod
            ? $payment->payment_method
            : PaymentMethod::from((string) $payment->payment_method);

        $cash = $this->accounts->cashForMethod($method);
        $payableType = $payment->payable_type instanceof InvoicePayableType
            ? $payment->payable_type
            : InvoicePayableType::from((string) $payment->payable_type);

        $linkableType = null;
        $linkableId = null;
        $partyAccountId = null;

        if ($payableType === InvoicePayableType::SalesInvoice) {
            $invoice = SalesInvoice::query()->findOrFail($payment->payable_id);
            $partyAccountId = $this->accounts->receivableForCustomer(
                $invoice->customer_id ? (int) $invoice->customer_id : null
            )->id;
            $linkableType = $invoice->customer_id ? Client::class : null;
            $linkableId = $invoice->customer_id;
            // Dr cash, Cr AR
            $entries = [
                ['account_tree_id' => $cash->id, 'debit' => $amount, 'credit' => null, 'notes' => $payment->document_number],
                ['account_tree_id' => $partyAccountId, 'debit' => null, 'credit' => $amount, 'notes' => $payment->document_number],
            ];
        } else {
            $invoice = PurchaseInvoice::query()->findOrFail($payment->payable_id);
            $partyAccountId = $this->accounts->payableForSupplier((int) $invoice->supplier_id)->id;
            $linkableType = Supplier::class;
            $linkableId = $invoice->supplier_id;
            // Dr AP, Cr cash
            $entries = [
                ['account_tree_id' => $partyAccountId, 'debit' => $amount, 'credit' => null, 'notes' => $payment->document_number],
                ['account_tree_id' => $cash->id, 'debit' => null, 'credit' => $amount, 'notes' => $payment->document_number],
            ];
        }

        $date = $payment->paid_at
            ? Carbon::parse($payment->paid_at)->toDateString()
            : now()->toDateString();
        $period = $this->periods->resolveOpenForDate($date);
        $userId = Auth::guard('tenant')->id();

        return $this->writer->createOperationWithEntries([
            'financial_period_id' => $period?->id,
            'date' => $date,
            'comment' => __('dashboard.accounting_posting.invoice_payment', [
                'number' => $payment->document_number,
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
            'linkable_type' => $linkableType,
            'linkable_id' => $linkableId,
            'service_type' => $payment->getMorphClass(),
            'service_id' => $payment->id,
        ], $entries);
    }
}
