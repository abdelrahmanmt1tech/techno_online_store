<?php

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $content = $this->content ?? [];

        return match ($this->type) {
            'hero' => $this->resolveHero($content),
            'categories' => $this->resolveCategories($content),
            'new_arrivals' => $this->resolveNewArrivals($content),
            'best_sellers' => $this->resolveBestSellers($content),
            'deals' => $this->resolveDeals($content),
            'testimonials' => $this->resolveTestimonials($content),
            default => ['type' => $this->type, 'sort_order' => $this->sort_order],
        };
    }

    private function resolveHero(array $content): array
    {
        return [
            'type' => 'hero',
            'sort_order' => $this->sort_order,
            'title' => $content['title'] ?? '',
            'subtitle' => $content['subtitle'] ?? '',
            'button_text' => $content['button_text'] ?? '',
            'button_url' => $content['button_url'] ?? '',
            'image' => $this->imageUrl($content['image'] ?? null),
        ];
    }

    private function resolveCategories(array $content): array
    {
        $categoryIds = $content['category_ids'] ?? [];
        $categories = Category::whereIn('id', $categoryIds)
            ->where('is_active', true)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'image' => $this->imageUrl($c->image ?? null),
            ]);

        return [
            'type' => 'categories',
            'sort_order' => $this->sort_order,
            'title' => $content['title'] ?? '',
            'categories' => $categories,
        ];
    }

    private function resolveNewArrivals(array $content): array
    {
        $limit = $content['products_count'] ?? 8;
        $products = Product::where('is_active', true)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->with('media')
            ->get()
            ->map(fn ($p) => new ProductListResource($p));

        return [
            'type' => 'new_arrivals',
            'sort_order' => $this->sort_order,
            'title' => $content['title'] ?? '',
            'subtitle' => $content['subtitle'] ?? '',
            'products' => $products,
        ];
    }

    private function resolveBestSellers(array $content): array
    {
        $limit = $content['products_count'] ?? 8;
        $products = Product::where('is_active', true)
            ->withCount(['orderItems as sales_count' => fn ($q) => $q->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('orders.status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered'])])
            ->orderByDesc('sales_count')
            ->limit($limit)
            ->with('media')
            ->get()
            ->map(fn ($p) => new ProductListResource($p));

        return [
            'type' => 'best_sellers',
            'sort_order' => $this->sort_order,
            'title' => $content['title'] ?? '',
            'subtitle' => $content['subtitle'] ?? '',
            'products' => $products,
        ];
    }

    private function resolveDeals(array $content): array
    {
        $limit = $content['products_count'] ?? 8;
        $products = Product::where('is_active', true)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->with('media')
            ->get()
            ->map(fn ($p) => new ProductListResource($p));

        return [
            'type' => 'deals',
            'sort_order' => $this->sort_order,
            'title' => $content['title'] ?? '',
            'subtitle' => $content['subtitle'] ?? '',
            'products' => $products,
        ];
    }

    private function resolveTestimonials(array $content): array
    {
        $items = collect($content['items'] ?? [])->map(fn ($item) => [
            'customer_name' => $item['customer_name'] ?? '',
            'customer_image' => $this->imageUrl($item['customer_image'] ?? null),
            'review' => $item['review'] ?? '',
            'rating' => (int) ($item['rating'] ?? 5),
        ]);

        return [
            'type' => 'testimonials',
            'sort_order' => $this->sort_order,
            'title' => $content['title'] ?? '',
            'subtitle' => $content['subtitle'] ?? '',
            'items' => $items,
        ];
    }

    private function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return asset('storage/tenant'.tenant('id').'/'.$path);
    }
}
