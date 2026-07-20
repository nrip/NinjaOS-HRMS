<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Models\Location;

/**
 * GeoFencingService
 *
 * Implements the Haversine formula to calculate the great-circle distance
 * between two GPS coordinates, and validates whether a punch is within the
 * allowed radius for a given location.
 *
 * Radius resolution order:
 *   1. Location->attendance_radius_meters (per-location DB value)
 *   2. config('nexusos.geofencing.default_radius_meters') (default: 100m)
 */
final class GeoFencingService
{
    /** Earth's mean radius in metres */
    private const EARTH_RADIUS_METRES = 6_371_000;

    /**
     * Calculate the Haversine distance in metres between two GPS coordinates.
     *
     * @param  float  $lat1  Latitude of point A (decimal degrees)
     * @param  float  $lng1  Longitude of point A (decimal degrees)
     * @param  float  $lat2  Latitude of point B (decimal degrees)
     * @param  float  $lng2  Longitude of point B (decimal degrees)
     * @return float  Distance in metres
     */
    public function distanceMetres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METRES * $c;
    }

    /**
     * Validate whether a punch coordinate is within the allowed radius
     * for the given location.
     *
     * @param  Location  $location  The office location (must have gis_lat, gis_lng)
     * @param  float     $punchLat  Latitude from the punch payload
     * @param  float     $punchLng  Longitude from the punch payload
     * @return array{
     *   allowed: bool,
     *   distance_metres: int,
     *   allowed_radius_metres: int,
     *   message: string
     * }
     */
    public function validate(Location $location, float $punchLat, float $punchLng): array
    {
        $officeLat = (float) $location->gis_lat;
        $officeLng = (float) $location->gis_lng;

        $distanceMetres = (int) round(
            $this->distanceMetres($officeLat, $officeLng, $punchLat, $punchLng)
        );

        $allowedRadius = $this->resolveRadius($location);
        $allowed = $distanceMetres <= $allowedRadius;

        $message = $allowed
            ? "Punch accepted. You are {$distanceMetres}m from the office (limit: {$allowedRadius}m)."
            : "Punch rejected: you are {$distanceMetres}m from the office. Maximum allowed distance is {$allowedRadius}m.";

        return [
            'allowed'               => $allowed,
            'distance_metres'       => $distanceMetres,
            'allowed_radius_metres' => $allowedRadius,
            'message'               => $message,
        ];
    }

    /**
     * Resolve the effective geo-fence radius for a location.
     *
     * Priority:
     *   1. Per-location DB value (attendance_radius_meters)
     *   2. Application config default (nexusos.geofencing.default_radius_meters)
     */
    public function resolveRadius(Location $location): int
    {
        return $location->attendance_radius_meters
            ?? (int) config('nexusos.geofencing.default_radius_meters', 100);
    }
}
