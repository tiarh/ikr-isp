<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CoverageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FieldOpsController extends Controller
{
    public function __construct(private CoverageService $coverage) {}

    public function odpAssets(Request $request): JsonResponse
    {
        $request->validate([
            'lat'    => 'required|numeric',
            'lng'    => 'required|numeric',
            'radius' => 'nullable|integer',
        ]);
        $odps = $this->coverage->findNearestOdps(
            (float) $request->lat,
            (float) $request->lng,
            (int) ($request->radius ?? 300),
        );
        return response()->json(['data' => $odps]);
    }

    public function oltAssets(Request $request): JsonResponse
    {
        $request->validate([
            'area_id' => 'nullable|integer',
        ]);
        // Stub: panggil FieldOps /olt-assets
        try {
            $http = new \GuzzleHttp\Client([
                'base_uri' => config('psb.fieldops.api_url'),
                'timeout'  => 15,
                'headers'  => [
                    'Authorization' => 'Bearer ' . config('psb.fieldops.api_key'),
                    'Accept'        => 'application/json',
                ],
            ]);
            $res = $http->get('/olt-assets', ['query' => $request->only('area_id')]);
            return response()->json(json_decode($res->getBody()->getContents(), true));
        } catch (\Throwable $e) {
            return response()->json(['data' => [], 'error' => $e->getMessage()], 500);
        }
    }
}
