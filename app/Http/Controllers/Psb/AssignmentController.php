<?php

namespace App\Http\Controllers\Psb;

use App\Enums\PsbStatus;
use App\Http\Controllers\Controller;
use App\Models\PsbOrder;
use App\Models\User;
use App\Services\PsbStateMachine;
use App\Services\TeknisiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function __construct(
        private TeknisiService $teknisi,
        private PsbStateMachine $sm,
    ) {}

    public function index(): Response
    {
        $orders = PsbOrder::where('status', PsbStatus::CoverageOk)
            ->with('teknisi')
            ->latest()
            ->get();

        // List teknisi, sort by open ticket count (idle first)
        // TeknisiService sudah punya fallback ke local user kalau eBilling gak reachable
        try {
            $teknisis = $this->teknisi->list();
        } catch (\Throwable $e) {
            $teknisis = [];
        }

        return Inertia::render('Psb/Assignment', [
            'orders'   => $orders,
            'teknisis' => $teknisis,
        ]);
    }

    public function assign(Request $request, PsbOrder $psbOrder): RedirectResponse
    {
        $data = $request->validate([
            'teknisi_ids'   => 'required|array|min:1',
            'teknisi_ids.*' => 'integer|exists:users,id',
            'primary_id'    => 'required|integer|exists:users,id',
        ]);

        // Sync teknisi ke pivot
        $psbOrder->teknisi()->detach();
        foreach ($data['teknisi_ids'] as $tid) {
            $psbOrder->teknisi()->attach($tid, [
                'role'        => $tid == $data['primary_id'] ? 'lead' : 'assistant',
                'assigned_at' => now(),
            ]);
        }

        $psbOrder->update([
            'primary_teknisi_id' => $data['primary_id'],
            'leader_teknisi_id'  => $request->user()->id,
        ]);

        $this->sm->transition($psbOrder, PsbStatus::Assigned, $request->user(), 'Assigned teknisi');

        return back()->with('success', 'Teknisi assigned');
    }
}
