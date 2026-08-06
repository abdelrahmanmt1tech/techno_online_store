<?php

namespace App\Http\Controllers\Api\Central;

use App\Http\Controllers\Controller;
use App\Http\Resources\Central\PageDetailsResource;
use App\Http\Resources\Central\PageListResource;
use App\Models\Page;
use App\Traits\ApiResponse;

class PageController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $pages = Page::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse(
            PageListResource::collection($pages),
            __('messages.fetched_successfully'),
        );
    }

    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)
            ->where('is_active', true)
            ->with('seo')
            ->first();

        if (! $page) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        return $this->successResponse(
            PageDetailsResource::make($page),
            __('messages.fetched_successfully'),
        );
    }
}
