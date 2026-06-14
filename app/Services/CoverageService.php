<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Coverage check service.
 * Query FieldOps untuk ODP assets terdekat, hitung jarak dgn Haversine.
 */
class CoverageService
{
    private Client $http;
    private int $defaultRadius;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => config('psb.fieldops.api_url'),
            'timeout'  => config('psb.fieldops.timeout', 15),
            'headers'  => [
                'Authorization' => 'Bearer ' . config('psb.fieldops.api_key'),
                'Accept'        => 'application/json',
            ],
        ]);
        $this->defaultRadius = config('psb.coverage_radius_m', 300);
    }

    /**
     * Haversine distance (meter) between 2 lat/lng points.
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * GET FieldOps /odp-assets?lat=&lng=&radius=
     * Return list of ODP nearest to (lat, lng) within radius (m).
     *
     * @return array<int, array{
     *   id:int, code:string, name:string, lat:float, lng:float, distance_m:float
     * }>
     */
    public function findNearestOdps(float $lat, float $lng, ?int $radius = null): array
    {
        $radius = $radius ?? $this->defaultRadius;
        try {
            $res = $this->http->get('/odp-assets', [
                'query' => [
                    'lat'    => $lat,
                    'lng'    => $lng,
                    'radius' => $radius,
                ],
            ]);
            $body = json_decode($res->getBody()->getContents(), true);
            $odps = $body['data'] ?? [];
            return collect($odps)->map(function ($o) use ($lat, $lng) {
                $o['distance_m'] = $this->calculateDistance($lat, $lng, $o['lat'], $o['lng']);
                return $o;
            })->sortBy('distance_m')->values()->all();
        } catch (\Throwable $e) {
            Log::error('FieldOps ODP lookup failed', ['err' => $e->getMessage()]);
            return [];
        }
    }

    public function getOdpsById(int $id): ?array
    {
        try {
            $res = $this->http->get("/odp-assets/{$id}");
            return json_decode($res->getBody()->getContents(), true)['data'] ?? null;
        } catch (\Throwable $e) {
            Log::error('FieldOps ODP by id failed', ['err' => $e->getMessage()]);
            return null;
        }
    }
}
