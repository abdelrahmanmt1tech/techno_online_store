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

        $countryId = (int) $request->country_id;

        $packages = Package::where('is_active', true)
            ->orderBy('sort')
            ->with(['prices.country', 'prices.currency'])
            ->get();

        $packages->each(function (Package $package) use ($countryId) {
            $countryPrice = $package->prices->firstWhere('country_id', $countryId);
            $resolvedPrice = $countryPrice ?? $package->prices->firstWhere('is_default', true);

            $package->setRelation('prices', $resolvedPrice ? collect([$resolvedPrice]) : collect());
        });

        return $this->successResponse(PackageResource::collection($packages));
    }
}
