<?php

namespace App\Http\Controllers\Api\Central;

use App\Http\Controllers\Controller;
use App\Http\Resources\Central\PackageResource;
use App\Models\Country;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;

class PackageController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        if (! $request->filled('country_id')) {
            $country = $this->resolveCountryFromRequest();

            if ($country) {
                $request->merge(['country_id' => $country->id]);
            }
        }

        $packages = Package::where('is_active', true)
            ->orderBy('sort')
            ->with(['prices.country', 'prices.currency'])
            ->get();

        return $this->successResponse(PackageResource::collection($packages));
    }

    private function resolveCountryFromRequest(): ?Country
    {
        $countryCode = $this->detectCountryCode();

        if (! $countryCode) {
            return null;
        }

        $country = Country::where('country_code', $countryCode)->first();

        if (! $country) {
            return null;
        }

        $hasPrices = PackagePrice::where('country_id', $country->id)->exists();

        return $hasPrices ? $country : null;
    }

    private function detectCountryCode(): ?string
    {
        $country = request()->header('CF-IPCountry');

        if (! $country) {
            try {
                $response = Http::timeout(2)
                    ->get('http://ip-api.com/json/'.request()->ip());

                if ($response->successful()) {
                    $country = $response->json()['countryCode'] ?? 'US';
                }
            } catch (Exception $e) {
                $country = 'US';
            }
        }

        return $country ?: null;
    }
}
