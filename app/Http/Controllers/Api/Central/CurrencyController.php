<?php

namespace App\Http\Controllers\Api\Central;

use App\Http\Controllers\Controller;
use App\Http\Resources\Central\CurrencyResource;
use App\Models\Currency;
use App\Traits\ApiResponse;

class CurrencyController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $currencies = Currency::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse(
            CurrencyResource::collection($currencies),
            __('messages.fetched_successfully'),
        );
    }
}
