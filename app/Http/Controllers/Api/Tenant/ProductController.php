<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\ProductDetailResource;
use App\Http\Resources\Tenant\ProductListResource;
use App\Models\Tenant\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Product::where('is_active', true)
            ->with(['categories', 'media', 'variants']);

        if ($request->filled('category_id')) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $request->category_id));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }

        $perPage = min((int) ($request->per_page ?? 12), 50);
        $products = $query->orderBy('order')->paginate($perPage);

        return $this->paginatedResponse(
            $products,
            ProductListResource::collection($products),
        );
    }

    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'categories',
                'media',
                'variations.options',
                'variants' => fn ($q) => $q->where('is_active', true),
                'variants.options',
            ])
            ->first();

        if (! $product) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        return $this->successResponse(
            ProductDetailResource::make($product),
        );
    }
}
