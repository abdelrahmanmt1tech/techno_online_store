<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\PageDetailsResource;
use App\Http\Resources\Tenant\PageResource;
use App\Models\Tenant\Page;
use App\Traits\ApiResponse;

class PageController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $pages = Page::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse(PageResource::collection($pages));
    }

    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)
            ->where('is_active', true)
            ->with('seo')
            ->first();

        if (! $page) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(PageDetailsResource::make($page));
    }
}
