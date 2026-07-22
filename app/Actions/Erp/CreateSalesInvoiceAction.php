<?php

namespace App\Actions\Erp;

use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\InvoiceStatus;
use App\Enums\Erp\SaleStatus;
use App\Models\Tenant\Sale;
use App\Models\Tenant\SaleItem;
use App\Models\Tenant\SalesInvoice;
use App\Models\Tenant\SalesInvoiceItem;
use App\Services\Erp\DocumentNumberService;
use App\Services\Erp\InvoicePrintSettingsService;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * إنشاء فاتورة بيع (كاملة أو جزئية) من Sale مؤكدة.
 * ينسخ order_id من Sale ويمنع تعارضه.
 */
final class CreateSalesInvoiceAction
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly InvoicePrintSettingsService $printSettings,
    ) {}

    /**
     * @param  list<array{sale_item_id: int, quantity: string|int|float}>|null  $lines  null = فاتورة كل المتبقي
     */
    public function execute(Sale $sale, ?array $lines = null, ?string $invoiceDate = null, ?string $dueDate = null): SalesInvoice
    {
        return DB::connection('tenant')->transaction(function () use ($sale, $lines, $invoiceDate, $dueDate) {
            /** @var Sale $locked */
            $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->with('items')->firstOrFail();

            if (! in_array($locked->status, [
                SaleStatus::Confirmed,
                SaleStatus::PartiallyInvoiced,
                SaleStatus::PartiallyReturned,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => __('erp.validation.sale_must_be_confirmed_to_invoice'),
                ]);
            }

            $payload = $lines ?? $locked->items->map(fn (SaleItem $item) => [
                'sale_item_id' => $item->id,
                'quantity' => Decimal::sub($item->quantity, $item->invoiced_quantity),
            ])->all();

            $invoice = SalesInvoice::query()->create([
                'document_number' => $this->numbers->next(DocumentSequenceType::SalesInvoice),
                'sale_id' => $locked->id,
                'order_id' => $locked->order_id,
                'customer_id' => $locked->customer_id,
                'branch_id' => $locked->branch_id,
                'invoice_date' => $invoiceDate ?? now()->toDateString(),
                'due_date' => $dueDate,
                'status' => InvoiceStatus::Issued->value,
                'currency_code' => $locked->currency_code,
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'issued_at' => now(),
                'created_by' => Auth::guard('tenant')->id(),
            ]);

            $subtotal = '0';
            $discount = '0';
            $tax = '0';

            foreach ($payload as $row) {
                if (! Decimal::isPositive((string) $row['quantity'])) {
                    continue;
                }

                /** @var SaleItem $saleItem */
                $saleItem = SaleItem::query()->whereKey($row['sale_item_id'])->lockForUpdate()->firstOrFail();
                if ($saleItem->sale_id !== $locked->id) {
                    throw ValidationException::withMessages([
                        'sale_item_id' => __('erp.validation.sale_item_mismatch'),
                    ]);
                }

                $qty = Decimal::of($row['quantity']);
                $remaining = Decimal::sub($saleItem->quantity, $saleItem->invoiced_quantity);
                if (Decimal::cmp($qty, $remaining) > 0) {
                    throw ValidationException::withMessages([
                        'quantity' => __('erp.validation.cannot_invoice_more_than_remaining', [
                            'remaining' => $remaining,
                        ]),
                    ]);
                }

                $ratio = Decimal::div($qty, $saleItem->quantity);
                $lineDiscount = Decimal::money(Decimal::mul($saleItem->discount ?? '0', $ratio, 4));
                $lineTax = Decimal::money(Decimal::mul($saleItem->tax ?? '0', $ratio, 4));
                $lineBase = Decimal::money(Decimal::mul($qty, $saleItem->unit_price));
                $lineTotal = Decimal::money(Decimal::add(Decimal::sub($lineBase, $lineDiscount, 2), $lineTax, 2));

                SalesInvoiceItem::query()->create([
                    'sales_invoice_id' => $invoice->id,
                    'sale_item_id' => $saleItem->id,
                    'source_type' => $saleItem->source_type instanceof \BackedEnum
                        ? $saleItem->source_type->value
                        : $saleItem->source_type,
                    'description_snapshot' => $saleItem->description_snapshot,
                    'sku_snapshot' => $saleItem->sku_snapshot,
                    'variation_snapshot' => $saleItem->variation_snapshot,
                    'unit_id' => $saleItem->unit_id,
                    'quantity' => $qty,
                    'unit_price' => $saleItem->unit_price,
                    'discount' => $lineDiscount,
                    'tax' => $lineTax,
                    'line_total' => $lineTotal,
                ]);

                $saleItem->invoiced_quantity = Decimal::add($saleItem->invoiced_quantity, $qty);
                $saleItem->save();

                $subtotal = Decimal::add($subtotal, $lineBase, 2);
                $discount = Decimal::add($discount, $lineDiscount, 2);
                $tax = Decimal::add($tax, $lineTax, 2);
            }

            $grand = Decimal::money(Decimal::add(Decimal::sub($subtotal, $discount, 2), $tax, 2));
            $invoice->subtotal = Decimal::money($subtotal);
            $invoice->discount_total = Decimal::money($discount);
            $invoice->tax_total = Decimal::money($tax);
            $invoice->grand_total = $grand;
            $invoice->due_amount = $grand;
            $invoice->paid_amount = '0';
            $invoice->print_settings_snapshot = $this->printSettings->buildSnapshot();
            $invoice->save();

            $this->refreshSaleInvoiceStatus($locked);

            return $invoice->fresh('items');
        });
    }

    public function assertOrderMatchesSale(SalesInvoice $invoice): void
    {
        $sale = $invoice->sale;
        if ($invoice->order_id !== $sale?->order_id) {
            throw ValidationException::withMessages([
                'order_id' => __('erp.validation.invoice_order_must_match_sale'),
            ]);
        }
    }

    private function refreshSaleInvoiceStatus(Sale $sale): void
    {
        $sale->load('items');
        $all = true;
        $any = false;
        foreach ($sale->items as $item) {
            if (Decimal::isPositive($item->invoiced_quantity)) {
                $any = true;
            }
            if (Decimal::cmp($item->invoiced_quantity, $item->quantity) < 0) {
                $all = false;
            }
        }

        if ($all && $any) {
            $sale->status = SaleStatus::Invoiced;
        } elseif ($any) {
            $sale->status = SaleStatus::PartiallyInvoiced;
        }
        $sale->save();
    }
}
