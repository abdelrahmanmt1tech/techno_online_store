<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tenant\StoreReviewRequest;
use App\Http\Resources\Tenant\ReviewResource;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    use ApiResponse;

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $user = $request->user();

        $product = Product::find($request->product_id);

        if (! $product) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        $hasDeliveredOrder = Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->exists();

        if (! $hasDeliveredOrder) {
            return $this->errorResponse(__('messages.review_not_eligible'), 403);
        }

        $existingReview = Review::where('user_id', $user->id)
            ->where('reviewable_type', Product::class)
            ->where('reviewable_id', $product->id)
            ->exists();

        if ($existingReview) {
            return $this->errorResponse(__('messages.review_already_exists'), 422);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'reviewable_type' => Product::class,
            'reviewable_id' => $product->id,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
        ]);

        return $this->createdResponse(new ReviewResource($review->load('user')));
    }

    public function index(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->where('is_active', true)->first();

        if (! $product) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        $reviews = Review::where('reviewable_type', Product::class)
            ->where('reviewable_id', $product->id)
            ->where('is_approved', true)
            ->with('user:id,name')
            ->latest()
            ->paginate(15);

        return $this->paginatedResponse($reviews, ReviewResource::collection($reviews));
    }
}
