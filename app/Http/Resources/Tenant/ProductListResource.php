<?php

namespace App\Http\Resources\Tenant;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ProductListResource extends JsonResource
{
    private static ?array $currencyCache = null;

    public function toArray(Request $request): array
    {
        $firstVariant = $this->variants?->first();

        $price = $firstVariant?->price ?? $this->price;
        $salePrice = $firstVariant?->sale_price ?? $firstVariant?->price ?? $this->sale_price;

        $discountPercent = $price > 0 && $salePrice !== null && $salePrice < $price
            ? round((($price - $salePrice) / $price) * 100)
            : 0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $price,
            'sale_price' => $salePrice,
            'discount_percent' => $discountPercent,
            'image' => $this->whenLoaded('media', fn () => $this->media->first() ? asset('storage/tenant'.tenant('id').'/'.$this->media->first()->file) : null),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'is_favorite' => $this->is_favorite ?? false,
            'rating' => $this->whenAggregated('reviews', 'rating', 'avg'),
            'reviews_count' => $this->whenCounted('reviews'),
            'currency' => static::getCurrency(),
        ];
    }

    public static function getCurrency(): ?array
    {
        if (static::$currencyCache !== null) {
            return static::$currencyCache;
        }

        $currencyCode = Setting::where('key', 'site_currency')->value('value');

        if (! $currencyCode) {
            return static::$currencyCache = null;
        }

        $row = DB::connection(
            config('tenancy.database.central_connection', config('database.default'))
        )
            ->table('currencies')
            ->where('code', $currencyCode)
            ->where('is_active', true)
            ->first();

        if (! $row) {
            return static::$currencyCache = null;
        }

        $locale = app()->getLocale();
        $name = json_decode($row->name, true)[$locale] ?? $row->code;

        return static::$currencyCache = [
            'code' => $row->code,
            'name' => $name,
            'symbol' => $row->symbol ?? null,
        ];
    }
}
