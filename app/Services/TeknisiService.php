<?php

namespace App\Services;

use App\Models\User;
use GuzzleHttp\Client;

/**
 * Lookup teknisi dari eBilling (role=teknisi / leader_teknisi).
 *
 * Endpoint: GET {EBILLING_API_URL}/teknisi?role=teknisi,leader_teknisi
 * Filter idle (open_ticket = 0) di sisi IKR-ISP, sort by ticket count ASC.
 */
class TeknisiService
{
    public function __construct() {}

    /**
     * @return array<int, array{
     *   id:int, name:string, email:string, phone:?string, role:string, open_tickets:int
     * }>
     */
    public function list(bool $onlyIdle = false, ?string $role = null): array
    {
        $role = $role ?? 'teknisi';

        try {
            // Approach 1: eBilling REST API
            $http = new Client([
                'base_uri' => config('psb.ebilling.api_url'),
                'timeout'  => 15,
                'headers'  => [
                    'Authorization' => 'Bearer ' . config('psb.ebilling.api_key'),
                    'Accept'        => 'application/json',
                ],
            ]);
            $query = [];
            if ($role) {
                $query['role'] = $role;
            }
            $res = $http->get(config('psb.ebilling.teknisi_endpoint', '/teknisi'), [
                'query' => $query,
            ]);
            $data = json_decode($res->getBody()->getContents(), true);
            $teknisis = $data['data'] ?? [];
        } catch (\Throwable $e) {
            // Fallback: direct DB query ke eBilling
            $teknisis = \DB::connection('ebilling')
                ->table('users')
                ->whereIn('role', ['teknisi'])
                ->get(['id', 'name', 'email', 'phone', 'role'])
                ->map(fn($u) => (array) $u)
                ->all();
        }

        // Hitung open ticket per teknisi
        foreach ($teknisis as &$t) {
            try {
                $openCount = \DB::connection('ebilling')
                    ->table('support_tickets')
                    ->where('teknisi_id', $t['id'])
                    ->whereNotIn('status', ['closed', 'resolved'])
                    ->count();
                $t['open_tickets'] = $openCount;
            } catch (\Throwable $e) {
                $t['open_tickets'] = 0;
            }
        }

        // Filter idle kalau diminta
        if ($onlyIdle) {
            $teknisis = array_filter($teknisis, fn($t) => $t['open_tickets'] === 0);
        }

        // Sort by open_tickets ASC
        usort($teknisis, fn($a, $b) => $a['open_tickets'] <=> $b['open_tickets']);

        return array_values($teknisis);
    }
}
