<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Governorate;
use App\Traits\ApiResponse;

class GovernorateController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $governorates = Governorate::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'shipping_cost' => $g->shipping_cost,
            ]);

        return $this->successResponse($governorates);
    }
}
