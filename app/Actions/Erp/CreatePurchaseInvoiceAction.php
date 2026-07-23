<?php

namespace App\Actions\Erp;

use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\InvoiceStatus;
use App\Models\Tenant\GoodsReceipt;
use App\Models\Tenant\PurchaseInvoice;
use App\Models\Tenant\PurchaseInvoiceItem;
use App\Services\Erp\DocumentNumberService;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreatePurchaseInvoiceAction
{
    public function __construct(private readonly DocumentNumberService $numbers) {}

    public function execute(GoodsReceipt $receipt, ?string $invoiceDate = null): PurchaseInvoice
    {
        return DB::connection('tenant')->transaction(function () use ($receipt, $invoiceDate) {
            $gr = GoodsReceipt::query()->whereKey($receipt->id)->with('items')->firstOrFail();

            $status = $gr->status instanceof DocumentStatus
                ? $gr->status
                : DocumentStatus::from($gr->status);

            if ($status !== DocumentStatus::Posted) {
                throw ValidationException::withMessages([
                    'status' => __('erp.validation.goods_receipt_must_be_posted'),
                ]);
            }

            $invoice = PurchaseInvoice::query()->create([
                'document_number' => $this->numbers->next(DocumentSequenceType::PurchaseInvoice),
                'supplier_id' => $gr->supplier_id,
                'purchase_order_id' => $gr->purchase_order_id,
                'goods_receipt_id' => $gr->id,
                'invoice_date' => $invoiceDate ?? now()->toDateString(),
                'status' => InvoiceStatus::Issued->value,
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
            foreach ($gr->items as $item) {
                $lineTotal = Decimal::money(Decimal::mul($item->quantity, $item->unit_cost));
                PurchaseInvoiceItem::query()->create([
                    'purchase_invoice_id' => $invoice->id,
                    'goods_receipt_item_id' => $item->id,
                    'line_type' => $item->line_type instanceof \BackedEnum ? $item->line_type->value : $item->line_type,
                    'description_snapshot' => $item->description_snapshot,
                    'sku_snapshot' => $item->sku_snapshot,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'discount' => 0,
                    'tax' => 0,
                    'line_total' => $lineTotal,
                ]);
                $subtotal = Decimal::add($subtotal, $lineTotal, 2);
            }

            $invoice->subtotal = Decimal::money($subtotal);
            $invoice->grand_total = Decimal::money($subtotal);
            $invoice->due_amount = Decimal::money($subtotal);
            $invoice->save();

            return $invoice->fresh('items');
        });
    }
}
