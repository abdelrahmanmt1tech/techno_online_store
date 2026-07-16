<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\GovernorateResource;
use App\Models\Tenant\Governorate;
use App\Traits\ApiResponse;

class GovernorateController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $governorates = Governorate::where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->successResponse(GovernorateResource::collection($governorates));
    }
}
