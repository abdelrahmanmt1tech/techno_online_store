<?php

namespace App\Services\Hr;

/**
 * Haversine distance in meters between two WGS84 points.
 */
final class GeolocationService
{
    public function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371000.0;
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lon2 - $lon1);

        $a = sin($Δφ / 2) ** 2
            + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }

    public function isWithinRadius(
        float $lat,
        float $lon,
        float $targetLat,
        float $targetLon,
        int $radiusMeters,
    ): bool {
        return $this->distanceMeters($lat, $lon, $targetLat, $targetLon) <= $radiusMeters;
    }
}
