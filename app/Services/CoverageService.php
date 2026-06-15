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

            // bug #7 fix: kalau API down, fallback ke local DB via ExternalDb
            if (empty($odps)) {
                return $this->findNearestOdpsFromLocal($lat, $lng, $radius);
            }

            return collect($odps)->map(function ($o) use ($lat, $lng) {
                $o['distance_m'] = $this->calculateDistance($lat, $lng, $o['lat'], $o['lng']);
                return $o;
            })->sortBy('distance_m')->values()->all();
        } catch (\Throwable $e) {
            Log::error('FieldOps ODP lookup failed, trying local fallback', ['err' => $e->getMessage()]);
            return $this->findNearestOdpsFromLocal($lat, $lng, $radius);
        }
    }

    /**
     * bug #7 fix: fallback ke local DB (FieldOps share table) kalau API down
     * @return array<int, array{id:int, code:string, name:string, lat:float, lng:float, distance_m:float}>
     */
    private function findNearestOdpsFromLocal(float $lat, float $lng, int $radius): array
    {
        try {
            $conn = \App\Services\ExternalDb::connection('fieldops');
            if ($conn === null) {
                return [];
            }
            $rows = $conn->table('odp_assets')
                ->select('id', 'code', 'name', 'lat', 'lng')
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->get();
            return collect($rows)->map(function ($o) use ($lat, $lng) {
                $o = (array) $o;
                $o['lat'] = (float) $o['lat'];
                $o['lng'] = (float) $o['lng'];
                $o['distance_m'] = $this->calculateDistance($lat, $lng, $o['lat'], $o['lng']);
                return $o;
            })->filter(fn ($o) => $o['distance_m'] <= $radius)
              ->sortBy('distance_m')
              ->values()
              ->all();
        } catch (\Throwable $e) {
            Log::error('FieldOps local fallback failed', ['err' => $e->getMessage()]);
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

    /**
     * ODP-A: tambah ODC fetch (Optical Distribution Cabinet — parent dari ODP).
     * FieldOps endpoint: GET /odc-assets?lat=&lng=&radius=
     * Return sama shape dgn ODP (id, code, name, lat, lng, distance_m).
     *
     * @return array<int, array{id:int, code:string, name:string, lat:float, lng:float, distance_m:float}>
     */
    public function findNearestOdcs(float $lat, float $lng, ?int $radius = null): array
    {
        $radius = $radius ?? $this->defaultRadius;
        try {
            $res = $this->http->get('/odc-assets', [
                'query' => [
                    'lat'    => $lat,
                    'lng'    => $lng,
                    'radius' => $radius,
                ],
            ]);
            $body = json_decode($res->getBody()->getContents(), true);
            $odcs = $body['data'] ?? [];
            if (empty($odcs)) {
                return [];
            }
            return collect($odcs)->map(function ($o) use ($lat, $lng) {
                $o['distance_m'] = $this->calculateDistance($lat, $lng, $o['lat'], $o['lng']);
                return $o;
            })->sortBy('distance_m')->values()->all();
        } catch (\Throwable $e) {
            Log::warning('FieldOps ODC lookup failed', ['err' => $e->getMessage()]);
            return [];
        }
    }
}
