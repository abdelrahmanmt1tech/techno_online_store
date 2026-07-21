<?php

namespace App\Http\Controllers\Api\Central;

use App\Http\Controllers\Controller;
use App\Http\Resources\Central\CountryResource;
use App\Models\Country;
use App\Traits\ApiResponse;

class CountryController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $countries = Country::where('is_active', true)
            ->with('currency')
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse(
            CountryResource::collection($countries),
            __('messages.fetched_successfully'),
        );
    }
}
