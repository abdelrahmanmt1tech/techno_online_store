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
            ->when($request->filled('code'), fn ($q) => $q->where('order_number', 'like', '%'.$request->input('code').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->input('payment_status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->with([
                'items.product',
                'items.variant',
                'governorate',
            ])
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 10));

        return $this->paginatedResponse($orders, OrderResource::collection($orders));
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

    public function cancel(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;

        if (! $customer) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        $order = Order::where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();

        if (! $order) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        if ($order->status !== 'pending') {
            return $this->errorResponse('Only pending orders can be cancelled', 422);
        }

        $order->update(['status' => 'cancelled']);

        return $this->successResponse(new OrderResource($order), __('messages.success'));
    }
}
