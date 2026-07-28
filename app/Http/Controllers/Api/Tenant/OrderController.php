<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\OrderResource;
use App\Models\Tenant\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        if (! $customer) {
            return $this->successResponse([], __('messages.fetched_successfully'));
        }

        $orders = Order::where('customer_id', $customer->id)
            ->with([
                'items.product',
                'items.variant',
                'governorate',
            ])
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 10));

        return $this->successResponse(
            OrderResource::collection($orders),
            __('messages.fetched_successfully'),
        );
    }

    public function showById(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;

        if (! $customer) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        $order = Order::where('id', $id)
            ->where('customer_id', $customer->id)
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
