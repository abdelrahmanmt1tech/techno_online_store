<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\BranchResource;
use App\Http\Resources\Tenant\SeoResource;
use App\Models\Tenant\Branch;
use App\Traits\ApiResponse;

class BranchController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $branches = Branch::with('seo')
            ->where('is_active', true)
            ->orderBy('is_main', 'desc')
            ->orderBy('name')
            ->get();

        return $this->successResponse(BranchResource::collection($branches));
    }

    public function show(Branch $branch)
    {
        $branch->load('seo');

        return $this->successResponse(BranchResource::make($branch));
    }
}
