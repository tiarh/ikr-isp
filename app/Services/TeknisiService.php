<?php

namespace App\Services;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Lookup teknisi dari eBilling (role=teknisi / leader_teknisi).
 *
 * Endpoint: GET {EBILLING_API_URL}/teknisi?role=teknisi,leader_teknisi
 * Filter idle (open_ticket = 0) di sisi IKR-ISP, sort by ticket count ASC.
 *
 * Resilience: kalau ebilling API + DB gak reachable, return list teknisi lokal
 * dengan open_tickets=0 (fallback untuk production yang belum integrasi eBilling).
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

        // Approach 1: eBilling REST API (if configured + reachable)
        $teknisis = [];
        $apiKey = config('psb.ebilling.api_key');
        $apiUrl = config('psb.ebilling.api_url');

        if (!empty($apiKey) && !empty($apiUrl)) {
            try {
                $http = new Client([
                    'base_uri' => $apiUrl,
                    'timeout'  => 15,
                    'headers'  => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Accept'        => 'application/json',
                    ],
                ]);
                $res = $http->get(config('psb.ebilling.teknisi_endpoint', '/teknisi'), [
                    'query' => $role ? ['role' => $role] : [],
                ]);
                $data = json_decode($res->getBody()->getContents(), true);
                $teknisis = $data['data'] ?? [];
            } catch (\Throwable $e) {
                Log::warning('TeknisiService: eBilling API failed, trying DB fallback', ['err' => $e->getMessage()]);
            }
        }

        // Approach 2: eBilling direct DB (if not via API)
        // bug #6 fix: eBilling pake Spatie Permission, query via model_has_roles
        if (empty($teknisis)) {
            $conn = ExternalDb::connection('ebilling');
            if ($conn !== null) {
                try {
                    $teknisiIds = $conn->table('model_has_roles')
                        ->where('model_type', 'App\\Models\\User')
                        ->whereIn('role_id', function ($q) use ($conn) {
                            $q->select('id')->from('roles')
                                ->whereIn('name', ['teknisi', 'leader_teknisi']);
                        })
                        ->pluck('model_id')
                        ->all();
                    $teknisis = $conn->table('users')
                        ->whereIn('id', $teknisiIds)
                        ->get(['id', 'name', 'email', 'phone'])
                        ->map(function ($u) {
                            $u = (array) $u;
                            $u['role'] = 'teknisi';
                            return $u;
                        })
                        ->all();
                } catch (\Throwable $e) {
                    Log::warning('TeknisiService: eBilling DB query failed', ['err' => $e->getMessage()]);
                }
            }
        }

        // Approach 3: Fallback to local IKR-ISP users with role 'teknisi'
        // Use direct query to avoid Spatie RoleDoesNotExist when guard context
        // is sanctum (in API context) but roles are stored for 'web' guard.
        if (empty($teknisis)) {
            try {
                $teknisiIds = \DB::table('model_has_roles')
                    ->where('role_id', function ($q) {
                        $q->select('id')->from('roles')->where('name', 'teknisi');
                    })
                    ->pluck('model_id')
                    ->all();

                $teknisis = User::whereIn('id', $teknisiIds)
                    ->get(['id', 'name', 'email', 'phone'])
                    ->map(fn($u) => [
                        'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
                        'phone' => $u->phone ?? null, 'role' => 'teknisi',
                    ])
                    ->all();
            } catch (\Throwable $e) {
                Log::warning('TeknisiService: local user fallback failed', ['err' => $e->getMessage()]);
            }
        }

        // Hitung open ticket per teknisi
        $ebillingConn = ExternalDb::connection('ebilling');
        foreach ($teknisis as &$t) {
            if ($ebillingConn !== null) {
                try {
                    $openCount = $ebillingConn->table('support_tickets')
                        ->where('teknisi_id', $t['id'])
                        ->whereNotIn('status', ['closed', 'resolved'])
                        ->count();
                    $t['open_tickets'] = $openCount;
                } catch (\Throwable $e) {
                    $t['open_tickets'] = 0;
                }
            } else {
                $t['open_tickets'] = 0; // fallback when eBilling not available
            }
        }
        unset($t);

        // Filter idle kalau diminta
        if ($onlyIdle) {
            $teknisis = array_filter($teknisis, fn($t) => $t['open_tickets'] === 0);
        }

        // Sort by open_tickets ASC
        usort($teknisis, fn($a, $b) => $a['open_tickets'] <=> $b['open_tickets']);

        return array_values($teknisis);
    }
}
