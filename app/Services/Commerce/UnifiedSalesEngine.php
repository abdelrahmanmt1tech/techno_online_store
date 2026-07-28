<?php

namespace App\Services\Commerce;

use App\Actions\Erp\ConfirmSaleAction;
use App\Actions\Erp\CreateSalesInvoiceAction;
use App\Actions\Erp\PostSalesReturnAction;
use App\Actions\Erp\RecordInvoicePaymentAction;
use App\Enums\Commerce\SaleChannel;
use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\InvoicePayableType;
use App\Enums\Erp\PaymentMethod;
use App\Enums\Erp\SaleItemSourceType;
use App\Enums\Erp\SaleStatus;
use App\Enums\Pos\CashMovementType;
use App\Exceptions\Pos\PosRegisterGuardException;
use App\Models\Tenant\CashierSession;
use App\Models\Tenant\InvoicePayment;
use App\Models\Tenant\PosRegister;
use App\Models\Tenant\Sale;
use App\Models\Tenant\SaleItem;
use App\Models\Tenant\SalesInvoice;
use App\Models\Tenant\SalesReturn;
use App\Services\Erp\DocumentNumberService;
use App\Services\Pos\CashierSessionService;
use App\Services\Pos\CashMovementService;
use App\Services\Pos\PosReceiptNumberService;
use App\Services\Pos\PosRegisterGuard;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sales workflow orchestrator for ERP / POS / API.
 *
 * Controllers and Filament must call this engine — not Actions directly.
 * Store checkout remains outside this engine (unchanged).
 */
final class UnifiedSalesEngine
{
    public function __construct(
        private readonly ConfirmSaleAction $confirmSaleAction,
        private readonly CreateSalesInvoiceAction $createSalesInvoiceAction,
        private readonly RecordInvoicePaymentAction $recordInvoicePaymentAction,
        private readonly PostSalesReturnAction $postSalesReturnAction,
        private readonly DocumentNumberService $numbers,
        private readonly PosRegisterGuard $posGuard,
        private readonly PosReceiptNumberService $receipts,
        private readonly CashMovementService $cashMovements,
        private readonly CashierSessionService $cashierSessions,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $header
     */
    public function createDraftSale(SaleChannel $channel, array $header, array $items = []): Sale
    {
        return DB::connection('tenant')->transaction(function () use ($channel, $header, $items) {
            $context = $this->prepareChannelContext($channel, $header);

            $sale = new Sale;
            $sale->fill([
                'document_number' => $header['document_number']
                    ?? $this->numbers->next(DocumentSequenceType::Sale, $header['branch_id'] ?? null),
                'receipt_number' => $context['receipt_number'] ?? null,
                'source_type' => $channel->toSaleSourceType(),
                'order_id' => $header['order_id'] ?? null,
                'customer_id' => $header['customer_id'] ?? null,
                'branch_id' => $header['branch_id'] ?? ($context['register']->branch_id ?? null),
                'sale_date' => $header['sale_date'] ?? now()->toDateString(),
                'status' => SaleStatus::Draft,
                'currency_code' => $header['currency_code'] ?? 'EGP',
                'subtotal' => '0.00',
                'discount_total' => Decimal::money($header['discount_total'] ?? '0'),
                'tax_total' => Decimal::money($header['tax_total'] ?? '0'),
                'grand_total' => '0.00',
                'cost_total' => '0.0000',
                'profit_total' => '0.00',
                'notes' => $header['notes'] ?? null,
                'pos_register_id' => $context['register']->id ?? ($header['pos_register_id'] ?? null),
                'cashier_session_id' => $context['session']->id ?? ($header['cashier_session_id'] ?? null),
                'is_suspended' => false,
                'created_by' => Auth::guard('tenant')->id(),
            ]);
            $sale->save();

            foreach ($items as $row) {
                $line = new SaleItem;
                $line->fill($this->normalizeItemPayload($row));
                $sale->items()->save($line);
            }

            $this->recalculateHeaderTotals($sale->fresh(['items']));

            return $sale->fresh(['items']);
        });
    }

    public function confirm(Sale $sale): Sale
    {
        $this->assertNotSuspended($sale);
        $this->assertPosGuardForExistingSale($sale);

        return $this->confirmSaleAction->execute($sale);
    }

    /**
     * @param  list<array{sale_item_id: int, quantity: string|int|float}>|null  $lines
     */
    public function issueInvoice(Sale $sale, ?array $lines = null, ?string $invoiceDate = null, ?string $dueDate = null): SalesInvoice
    {
        $this->assertPosGuardForExistingSale($sale);

        return $this->createSalesInvoiceAction->execute($sale, $lines, $invoiceDate, $dueDate);
    }

    public function recordPayment(
        SalesInvoice $invoice,
        string $amount,
        PaymentMethod $method,
        ?string $paymentReference = null,
        ?string $paidAt = null,
        ?string $notes = null,
        ?string $idempotencyKey = null,
        ?string $posPaymentMethodType = null,
        ?string $posPaymentMethodCode = null,
    ): InvoicePayment {
        $sale = $invoice->sale;
        $this->assertPosGuardForExistingSale($sale);

        return DB::connection('tenant')->transaction(function () use (
            $invoice,
            $amount,
            $method,
            $paymentReference,
            $paidAt,
            $notes,
            $idempotencyKey,
            $posPaymentMethodType,
            $posPaymentMethodCode,
            $sale
        ) {
            $payment = $this->recordInvoicePaymentAction->execute(
                InvoicePayableType::SalesInvoice,
                $invoice->id,
                $amount,
                $method,
                $paymentReference,
                $paidAt,
                $notes,
                $idempotencyKey,
            );

            if ($sale?->cashier_session_id && $sale->source_type?->value === 'pos') {
                $session = CashierSession::query()->find($sale->cashier_session_id);
                if ($session) {
                    $this->cashMovements->record($session, CashMovementType::SalePayment, $amount, [
                        'payment_method_type' => $posPaymentMethodType ?? match ($method) {
                            PaymentMethod::Cash => 'cash',
                            PaymentMethod::Card => 'card',
                            PaymentMethod::BankTransfer => 'transfer',
                            default => 'other',
                        },
                        'payment_method_code' => $posPaymentMethodCode,
                        'direction' => 'in',
                        'sale_id' => $sale->id,
                        'sales_invoice_id' => $invoice->id,
                        'invoice_payment_id' => $payment->id,
                        'reference' => $payment->document_number ?? $paymentReference,
                    ]);
                }
            }

            return $payment;
        });
    }

    public function postReturn(SalesReturn $return): SalesReturn
    {
        return $this->postSalesReturnAction->execute($return);
    }

    /**
     * @return array{sale: Sale, invoice: ?SalesInvoice}
     */
    public function completeSale(Sale $sale, bool $issueInvoice = true): array
    {
        return DB::connection('tenant')->transaction(function () use ($sale, $issueInvoice) {
            $confirmed = $this->confirm($sale);
            $invoice = $issueInvoice ? $this->issueInvoice($confirmed) : null;

            return [
                'sale' => $confirmed->fresh(['items']),
                'invoice' => $invoice,
            ];
        });
    }

    public function suspend(Sale $sale): Sale
    {
        return DB::connection('tenant')->transaction(function () use ($sale) {
            /** @var Sale $locked */
            $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== SaleStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => __('commerce.validation.only_draft_can_suspend'),
                ]);
            }

            if ($locked->is_suspended) {
                throw ValidationException::withMessages([
                    'status' => __('commerce.validation.sale_already_suspended'),
                ]);
            }

            $settings = $this->cashierSessions->settings();
            if (! $settings->allow_suspend_sales) {
                throw ValidationException::withMessages([
                    'status' => __('commerce.validation.suspend_disabled'),
                ]);
            }

            $locked->is_suspended = true;
            $locked->suspended_at = now();
            $minutes = $settings->suspend_expires_minutes;
            $locked->suspended_until = $minutes ? now()->addMinutes((int) $minutes) : null;
            $locked->suspend_cancelled_at = null;
            $locked->suspend_cancelled_by = null;
            $locked->save();

            return $locked->fresh();
        });
    }

    public function resume(Sale $sale): Sale
    {
        return DB::connection('tenant')->transaction(function () use ($sale) {
            /** @var Sale $locked */
            $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if (! $locked->is_suspended) {
                throw ValidationException::withMessages([
                    'status' => __('commerce.validation.sale_not_suspended'),
                ]);
            }

            if ($locked->suspended_until && $locked->suspended_until->isPast()) {
                throw ValidationException::withMessages([
                    'status' => __('commerce.validation.suspended_sale_expired'),
                ]);
            }

            $locked->is_suspended = false;
            $locked->resumed_at = now();
            $locked->save();

            return $locked->fresh();
        });
    }

    public function cancelSuspended(Sale $sale, ?string $reason = null): Sale
    {
        return DB::connection('tenant')->transaction(function () use ($sale, $reason) {
            /** @var Sale $locked */
            $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if (! $locked->is_suspended) {
                throw ValidationException::withMessages([
                    'status' => __('commerce.validation.sale_not_suspended'),
                ]);
            }

            $locked->is_suspended = false;
            $locked->suspend_cancelled_at = now();
            $locked->suspend_cancelled_by = Auth::guard('tenant')->id();
            $locked->status = SaleStatus::Cancelled;
            if ($reason) {
                $locked->notes = trim(($locked->notes ? $locked->notes."\n" : '').$reason);
            }
            $locked->save();

            return $locked->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $header
     * @return array{register: ?PosRegister, session: ?CashierSession, receipt_number: ?string}
     */
    private function prepareChannelContext(SaleChannel $channel, array $header): array
    {
        if ($channel !== SaleChannel::Pos) {
            return ['register' => null, 'session' => null, 'receipt_number' => null];
        }

        $register = null;
        if (! empty($header['pos_register_id'])) {
            $register = PosRegister::query()->find($header['pos_register_id']);
        }

        $session = null;
        if (! empty($header['cashier_session_id'])) {
            $session = CashierSession::query()->find($header['cashier_session_id']);
        }

        try {
            $guarded = $this->posGuard->assertCanOperate($register, $session);
        } catch (PosRegisterGuardException $e) {
            throw ValidationException::withMessages([
                'cashier_session_id' => $e->getMessage(),
            ]);
        }

        return [
            'register' => $guarded['register'],
            'session' => $guarded['session'],
            'receipt_number' => $this->receipts->next($guarded['register']),
        ];
    }

    private function assertPosGuardForExistingSale(?Sale $sale): void
    {
        if (! $sale || $sale->source_type?->value !== 'pos') {
            return;
        }

        $register = $sale->pos_register_id
            ? PosRegister::query()->find($sale->pos_register_id)
            : null;
        $session = $sale->cashier_session_id
            ? CashierSession::query()->find($sale->cashier_session_id)
            : null;

        try {
            $this->posGuard->assertCanOperate($register, $session);
        } catch (PosRegisterGuardException $e) {
            throw ValidationException::withMessages([
                'cashier_session_id' => $e->getMessage(),
            ]);
        }
    }

    private function assertNotSuspended(Sale $sale): void
    {
        if ($sale->is_suspended) {
            throw ValidationException::withMessages([
                'status' => __('commerce.validation.sale_already_suspended'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeItemPayload(array $row): array
    {
        $qty = Decimal::of($row['quantity'] ?? '1');
        $price = Decimal::money($row['unit_price'] ?? '0');
        $discount = Decimal::money($row['discount'] ?? '0');
        $tax = Decimal::money($row['tax'] ?? '0');
        $lineTotal = Decimal::money(
            Decimal::sub(Decimal::add(Decimal::mul($qty, $price), $tax), $discount)
        );

        return [
            'source_type' => $row['source_type'] ?? SaleItemSourceType::Manual->value,
            'inventory_item_id' => $row['inventory_item_id'] ?? null,
            'product_id' => $row['product_id'] ?? null,
            'product_variant_id' => $row['product_variant_id'] ?? null,
            'warehouse_id' => $row['warehouse_id'] ?? null,
            'description_snapshot' => $row['description_snapshot'] ?? ($row['description'] ?? 'Item'),
            'sku_snapshot' => $row['sku_snapshot'] ?? null,
            'variation_snapshot' => $row['variation_snapshot'] ?? null,
            'unit_id' => $row['unit_id'] ?? null,
            'quantity' => $qty,
            'unit_price' => $price,
            'unit_cost' => Decimal::of($row['unit_cost'] ?? '0'),
            'discount' => $discount,
            'tax' => $tax,
            'line_total' => $lineTotal,
            'cost_total' => '0',
            'profit_total' => '0',
            'notes' => $row['notes'] ?? null,
        ];
    }

    private function recalculateHeaderTotals(Sale $sale): void
    {
        $subtotal = '0';
        $tax = '0';
        $lineDiscount = '0';

        foreach ($sale->items as $item) {
            $lineNet = Decimal::sub(
                Decimal::mul((string) $item->quantity, (string) $item->unit_price),
                (string) $item->discount
            );
            $subtotal = Decimal::add($subtotal, $lineNet);
            $tax = Decimal::add($tax, (string) $item->tax);
            $lineDiscount = Decimal::add($lineDiscount, (string) $item->discount);
        }

        $headerDiscount = Decimal::money($sale->discount_total ?? '0');
        $discount = Decimal::money(Decimal::add($headerDiscount, $lineDiscount));

        $sale->subtotal = Decimal::money($subtotal);
        $sale->tax_total = Decimal::money($tax);
        $sale->discount_total = $discount;
        $sale->grand_total = Decimal::money(
            Decimal::sub(Decimal::add($subtotal, $tax), $discount)
        );
        $sale->save();
    }
}
