<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Traits\ApiResponse;

class OrderController extends Controller
{
    use ApiResponse;

    public function show(string $token)
    {
        $order = Order::where('token', $token)
            ->with([
                'items.product',
                'items.variant',
                'governorate',
            ])
            ->first();

        if (! $order) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        return $this->successResponse([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_email' => $order->customer_email,
            'customer_address' => $order->customer_address,
            'governorate' => $order->governorate ? [
                'id' => $order->governorate->id,
                'name' => $order->governorate->name,
            ] : null,
            'governorate_name' => $order->governorate_name,
            'shipping_cost' => $order->shipping_cost,
            'coupon_code' => $order->coupon_code,
            'discount' => $order->discount,
            'subtotal' => $order->subtotal,
            'total' => $order->total,
            'notes' => $order->notes,
            'created_at' => $order->created_at,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'variant_options' => $item->variant_options,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->unit_price * $item->quantity,
            ]),
        ]);
    }
}
