<?php

namespace App\Http\Controllers\Api\Central;

use App\Http\Controllers\Controller;
use App\Http\Resources\Central\PackageResource;
use App\Models\Package;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $request->validate([
            'country_id' => 'required|integer|exists:countries,id',
        ]);

        $packages = Package::where('is_active', true)
            ->orderBy('sort')
            ->with(['prices.country', 'prices.currency'])
            ->get();

        return $this->successResponse(PackageResource::collection($packages));
    }
}
