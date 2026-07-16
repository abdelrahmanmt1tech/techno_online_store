<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\OrderResource;
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

        return $this->successResponse(new OrderResource($order));
    }
}
