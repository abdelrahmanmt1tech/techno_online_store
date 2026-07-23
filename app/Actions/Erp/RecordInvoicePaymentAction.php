<?php

namespace App\Actions\Erp;

use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\InvoicePayableType;
use App\Enums\Erp\InvoiceStatus;
use App\Enums\Erp\PaymentMethod;
use App\Models\Tenant\InvoicePayment;
use App\Models\Tenant\PurchaseInvoice;
use App\Models\Tenant\SalesInvoice;
use App\Services\Erp\DocumentNumberService;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecordInvoicePaymentAction
{
    public function __construct(private readonly DocumentNumberService $numbers) {}

    public function execute(
        InvoicePayableType $payableType,
        int $payableId,
        string $amount,
        PaymentMethod $method,
        ?string $paymentReference = null,
        ?string $paidAt = null,
        ?string $notes = null,
        ?string $idempotencyKey = null,
    ): InvoicePayment {
        return DB::connection('tenant')->transaction(function () use ($payableType, $payableId, $amount, $method, $paymentReference, $paidAt, $notes, $idempotencyKey) {
            if ($idempotencyKey) {
                $existing = InvoicePayment::query()
                    ->where('payment_reference', $idempotencyKey)
                    ->where('payable_type', $payableType->value)
                    ->where('payable_id', $payableId)
                    ->where('status', 'posted')
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            $money = Decimal::money($amount);
            if (! Decimal::isPositive($money, 2)) {
                throw ValidationException::withMessages([
                    'amount' => __('erp.validation.payment_amount_positive'),
                ]);
            }

            $invoice = match ($payableType) {
                InvoicePayableType::SalesInvoice => SalesInvoice::query()->whereKey($payableId)->lockForUpdate()->firstOrFail(),
                InvoicePayableType::PurchaseInvoice => PurchaseInvoice::query()->whereKey($payableId)->lockForUpdate()->firstOrFail(),
            };

            if (! in_array($invoice->status, [
                InvoiceStatus::Issued,
                InvoiceStatus::PartiallyPaid,
                InvoiceStatus::Overdue,
            ], true) && $invoice->status !== InvoiceStatus::Issued->value) {
                // handle string cast edge
            }

            $status = $invoice->status instanceof InvoiceStatus
                ? $invoice->status
                : InvoiceStatus::from($invoice->status);

            if (! in_array($status, [InvoiceStatus::Issued, InvoiceStatus::PartiallyPaid, InvoiceStatus::Overdue], true)) {
                throw ValidationException::withMessages([
                    'status' => __('erp.validation.invoice_not_payable'),
                ]);
            }

            $due = Decimal::money($invoice->due_amount);
            if (Decimal::cmp($money, $due, 2) > 0) {
                throw ValidationException::withMessages([
                    'amount' => __('erp.validation.payment_exceeds_due', ['due' => $due]),
                ]);
            }

            $payment = InvoicePayment::query()->create([
                'document_number' => $this->numbers->next(DocumentSequenceType::Payment),
                'payable_type' => $payableType->value,
                'payable_id' => $payableId,
                'payment_method' => $method->value,
                'amount' => $money,
                'payment_reference' => $paymentReference ?? $idempotencyKey,
                'paid_at' => $paidAt ?? now(),
                'notes' => $notes,
                'status' => 'posted',
                'created_by' => Auth::guard('tenant')->id(),
            ]);

            $invoice->paid_amount = Decimal::money(Decimal::add($invoice->paid_amount, $money, 2));
            $invoice->due_amount = Decimal::money(Decimal::sub($invoice->grand_total, $invoice->paid_amount, 2));

            if (Decimal::isZero($invoice->due_amount, 2)) {
                $invoice->status = InvoiceStatus::Paid;
            } else {
                $invoice->status = InvoiceStatus::PartiallyPaid;
            }
            $invoice->save();

            return $payment;
        });
    }
}
