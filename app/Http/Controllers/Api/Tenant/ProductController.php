<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tenant\ProductIndexRequest;
use App\Http\Resources\Tenant\ProductDetailResource;
use App\Http\Resources\Tenant\ProductListResource;
use App\Models\Tenant\Product;
use App\Traits\ApiResponse;

class ProductController extends Controller
{
    use ApiResponse;

    public function index(ProductIndexRequest $request)
    {
        $query = Product::where('is_active', true)
            ->with(['categories', 'media', 'variants']);

        if (auth('sanctum')->user()) {
            $query->withExists([
                'favorites as is_favorite' => fn ($q) => $q->where('user_id', auth('sanctum')->user()->id),
            ]);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $request->category_id));
        }

        if ($request->filled('search')) {
            $terms = array_filter(explode(' ', $request->search));
            foreach ($terms as $term) {
                $term = trim($term);
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                );
            }
        }

        $perPage = min((int) ($request->per_page ?? 12), 50);

        $sort = $request->input('sort', 'popular');

        if ($sort === 'popular') {
            $query->withCount(['orderItems as total_sold' => fn ($q) => $q->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('orders.status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered'])])
                ->orderBy('total_sold', 'desc');
        } else {
            $query->orderBy(match ($sort) {
                'newest' => 'created_at',
                'price_high' => 'price',
                'price_low' => 'price',
                default => 'order',
            }, $sort === 'price_high' ? 'desc' : 'asc');
        }

        $products = $query->paginate($perPage);

        return $this->paginatedResponse(
            $products,
            ProductListResource::collection($products),
        );
    }

    public function show(string $slug)
    {
        $productQuery = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'categories',
                'media',
                'variations.options',
                'variants' => fn ($q) => $q->where('is_active', true),
                'variants.options',
            ]);

        if (auth('sanctum')->user()) {
            $productQuery->withExists([
                'favorites as is_favorite' => fn ($q) => $q->where('user_id', auth('sanctum')->user()->id),
            ]);
        }

        $product = $productQuery->first();

        if (! $product) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        return $this->successResponse(
            ProductDetailResource::make($product),
        );
    }
}
