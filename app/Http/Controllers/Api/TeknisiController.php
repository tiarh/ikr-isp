<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ExternalDb;
use Illuminate\Http\JsonResponse;

class TeknisiController extends Controller
{
    public function index(): JsonResponse
    {
        // Hanya teknisi (bukan leader_teknisi) yang dipakai utk assignment step 3.
        // Leader teknisi bisa override manual via UI.
        //
        // NOTE: pakai direct query ke model_has_roles instead of User::role()
        // karena Spatie role lookup by default pakai 'web' guard, tapi API pakai
        // 'sanctum' guard. Kalau pakai User::role() tanpa specify guard, dan
        // roles belum di-register di sanctum guard, akan throw RoleDoesNotExist.
        $teknisiIds = \DB::table('model_has_roles')
            ->where('role_id', function ($q) {
                $q->select('id')->from('roles')->where('name', 'teknisi');
            })
            ->pluck('model_id')
            ->all();

        $teknisis = User::whereIn('id', $teknisiIds)
            ->get(['id', 'name', 'email', 'phone'])
            ->map(function ($user) {
                $activeTickets = 0;
                $ebillingConn = ExternalDb::connection('ebilling');
                if ($ebillingConn !== null) {
                    try {
                        $activeTickets = $ebillingConn->table('support_tickets')
                            ->where('teknisi_id', $user->id)
                            ->whereNotIn('status', ['closed', 'resolved'])
                            ->count();
                    } catch (\Throwable $e) {
                        $activeTickets = 0;
                    }
                }
                return [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'email'         => $user->email,
                    'phone'         => $user->phone ?? null,
                    'open_tickets'  => $activeTickets,
                ];
            })
            ->sortBy('open_tickets')
            ->values()
            ->all();

        return response()->json(['data' => $teknisis]);
    }
}
