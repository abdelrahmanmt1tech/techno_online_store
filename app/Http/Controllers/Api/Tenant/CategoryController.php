<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\CategoryResource;
use App\Models\Tenant\Category;
use App\Traits\ApiResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        return $this->successResponse( CategoryResource::collection($categories) );
    }

    public function show(string $slug)
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->with('seo')
            ->first();

        if (! $category) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        return $this->successResponse(
            CategoryResource::make($category),
        );
    }
}
