<?php

namespace App\Actions\Erp;

use App\Enums\Erp\PurchaseOrderStatus;
use App\Models\Tenant\PurchaseOrder;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApprovePurchaseOrderAction
{
    public function execute(PurchaseOrder $order): PurchaseOrder
    {
        return DB::connection('tenant')->transaction(function () use ($order) {
            $po = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();

            if ($po->status === PurchaseOrderStatus::Approved) {
                return $po;
            }

            if ($po->status !== PurchaseOrderStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => __('erp.validation.only_draft_po_can_approve'),
                ]);
            }

            $subtotal = '0';
            $discount = '0';
            $tax = '0';
            foreach ($po->items as $item) {
                $base = Decimal::mul($item->quantity, $item->unit_cost);
                $line = Decimal::money(Decimal::add(Decimal::sub($base, $item->discount ?? '0', 4), $item->tax ?? '0', 4));
                $item->line_total = $line;
                $item->save();
                $subtotal = Decimal::add($subtotal, $base, 2);
                $discount = Decimal::add($discount, $item->discount ?? '0', 2);
                $tax = Decimal::add($tax, $item->tax ?? '0', 2);
            }

            $po->subtotal = Decimal::money($subtotal);
            $po->discount_total = Decimal::money($discount);
            $po->tax_total = Decimal::money($tax);
            $po->grand_total = Decimal::money(Decimal::add(Decimal::sub($subtotal, $discount, 2), $tax, 2));
            $po->status = PurchaseOrderStatus::Approved;
            $po->approved_at = now();
            $po->approved_by = Auth::guard('tenant')->id();
            $po->save();

            return $po;
        });
    }
}
