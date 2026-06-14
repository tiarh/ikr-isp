<?php

namespace App\Http\Controllers\Psb;

use App\Enums\CoverageStatus;
use App\Enums\PsbStatus;
use App\Http\Controllers\Controller;
use App\Models\PsbOrder;
use App\Services\CoverageService;
use App\Services\PsbStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CoverageController extends Controller
{
    public function __construct(
        private CoverageService $coverage,
        private PsbStateMachine $sm,
    ) {}

    public function index(Request $request): Response
    {
        $orders = PsbOrder::query()
            ->where('status', PsbStatus::Submitted)
            ->orWhere('coverage_status', CoverageStatus::Pending)
            ->with('teknisi')
            ->latest()
            ->get()
            ->map(fn ($o) => [
                'id'              => $o->id,
                'customer_name'   => $o->customer_name,
                'customer_phone'  => $o->customer_phone,
                'village'         => $o->village,
                'package'         => $o->package,
                'gps_lat'         => $o->gps_lat,
                'gps_long'        => $o->gps_long,
                'coverage_status' => $o->coverage_status?->value,
                'odp_distance_m'  => $o->odp_distance_m,
                'status'          => $o->status->value,
                'created_at'      => $o->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Psb/Coverage', [
            'orders' => $orders,
        ]);
    }

    public function findNearestOdps(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat'    => 'required|numeric',
            'lng'    => 'required|numeric',
            'radius' => 'nullable|integer',
        ]);
        $odps = $this->coverage->findNearestOdps(
            (float) $data['lat'],
            (float) $data['lng'],
            $data['radius'] ?? null,
        );
        return response()->json(['data' => $odps]);
    }

    public function approve(Request $request, PsbOrder $psbOrder): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'odp_asset_id'   => 'required|integer',
            'odp_distance_m' => 'required|numeric',
            'odp_code'       => 'required|string|max:50',
            'odp_lat'        => 'required|numeric',
            'odp_lng'        => 'required|numeric',
        ]);
        $psbOrder->update([
            'odp_asset_id'     => $data['odp_asset_id'],
            'odp_distance_m'   => $data['odp_distance_m'],
            'odp_code'         => $data['odp_code'],
            'odp_lat'          => $data['odp_lat'],
            'odp_lng'          => $data['odp_lng'],
            'coverage_status'  => CoverageStatus::Approved,
        ]);
        $this->sm->transition($psbOrder, PsbStatus::CoverageOk, $request->user(), 'Coverage approved');
        return back()->with('success', 'Coverage approved');
    }

    public function reject(Request $request, PsbOrder $psbOrder): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'revision_note' => 'required|string|max:500',
        ]);
        $psbOrder->update([
            'coverage_status' => CoverageStatus::Rejected,
            'revision_note'   => $data['revision_note'],
        ]);
        // Jawaban #6: rejected → bukan final, nanti bisa revert via state machine
        $this->sm->transition($psbOrder, PsbStatus::Rejected, $request->user(), $data['revision_note']);
        return back()->with('success', 'Coverage rejected, order marked for revision');
    }
}
