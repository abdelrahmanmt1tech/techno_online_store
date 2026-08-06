<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Central\PageDetailsResource as CentralPageDetailsResource;
use App\Http\Resources\Central\PageListResource as CentralPageListResource;
use App\Http\Resources\Tenant\PageDetailsResource;
use App\Http\Resources\Tenant\PageResource;
use App\Models\Page;
use App\Models\Tenant\Page as TenantPage;
use App\Traits\ApiResponse;

class PageController extends Controller
{
    use ApiResponse;

    private function centralConnection(): string
    {
        return config('tenancy.database.central_connection', config('database.default'));
    }

    public function index()
    {
        if (tenant_module_enabled('store')) {
            $pages = TenantPage::where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            return $this->successResponse(PageResource::collection($pages));
        }

        $pages = Page::on($this->centralConnection())
            ->whereIn('slug', Page::PROTECTED_SLUGS)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse(CentralPageListResource::collection($pages));
    }

    public function show(string $slug)
    {
        if (tenant_module_enabled('store')) {
            $page = TenantPage::where('slug', $slug)
                ->where('is_active', true)
                ->with('seo')
                ->first();

            if (! $page) {
                return $this->notFoundResponse();
            }

            return $this->successResponse(PageDetailsResource::make($page));
        }

        $page = Page::on($this->centralConnection())
            ->whereIn('slug', Page::PROTECTED_SLUGS)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with('seo')
            ->first();

        if (! $page) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(CentralPageDetailsResource::make($page));
    }
}
