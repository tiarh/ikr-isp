<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

/**
 * Lookup sales/registrations data dari Saleskit (shared DB + REST API).
 */
class SaleskitBridgeService
{
    public function __construct() {}

    public function getRegistration(int $id): ?array
    {
        try {
            $http = new Client([
                'base_uri' => config('psb.saleskit.api_url'),
                'timeout'  => 15,
                'headers'  => [
                    'Authorization' => 'Bearer ' . config('psb.saleskit.api_key'),
                    'Accept'        => 'application/json',
                ],
            ]);
            $res = $http->get("/registrations/{$id}");
            return json_decode($res->getBody()->getContents(), true)['data'] ?? null;
        } catch (\Throwable $e) {
            return $this->getRegistrationFromDb($id);
        }
    }

    public function getRegistrationFromDb(int $id): ?array
    {
        try {
            $row = DB::connection('saleskit')
                ->table('registrations')
                ->where('id', $id)
                ->first();
            return $row ? (array) $row : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Create new PSB from saleskit registration data (Step 1 - Sales input).
     */
    public function createPsbFromRegistration(int $registrationId, array $extra = []): ?array
    {
        $reg = $this->getRegistration($registrationId);
        if (! $reg) {
            return null;
        }
        return array_merge($reg, $extra);
    }
}
