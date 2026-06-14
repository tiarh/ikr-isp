<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TeknisiController extends Controller
{
    public function index(): JsonResponse
    {
        // Hanya teknisi (bukan leader_teknisi) yang dipakai utk assignment step 3.
        // Leader teknisi bisa override manual via UI.
        $teknisis = User::role(['teknisi'])
            ->get(['id', 'name', 'email'])
            ->map(function ($user) {
                try {
                    $activeTickets = DB::connection('ebilling')
                        ->table('support_tickets')
                        ->where('teknisi_id', $user->id)
                        ->whereNotIn('status', ['closed', 'resolved'])
                        ->count();
                } catch (\Throwable $e) {
                    $activeTickets = 0;
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
