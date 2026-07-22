<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tenant\FavoriteRequest;
use App\Http\Resources\Tenant\FavoriteResource;
use App\Models\Tenant\Favorite;
use App\Models\Tenant\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    use ApiResponse;

    public function toggle(FavoriteRequest $request)
    {
        $user = $request->user();

        $product = Product::find($request->id);

        if (! $product) {
            return $this->errorResponse(__('dashboard.product_not_found'), 404);
        }

        $favorite = Favorite::where([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ])->first();

        if ($favorite) {
            $favorite->delete();

            return $this->successResponse([], __('dashboard.removed_from_favorites'));
        }

        Favorite::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        return $this->successResponse([], __('dashboard.added_to_favorites'));
    }

    public function getFavorites(Request $request)
    {
        $user = $request->user();

        $favorites = Favorite::where('user_id', $user->id)
            ->with('product')
            ->paginate(10);

        $products = $favorites->getCollection()->map(fn ($favorite) => $favorite->product->load(['media', 'categories']));

        return $this->paginatedResponse(
            $favorites,
            FavoriteResource::collection($products),
            __('dashboard.favorites_fetched_successfully'),
        );
    }
}
