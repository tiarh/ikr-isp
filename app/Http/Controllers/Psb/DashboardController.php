<?php

namespace App\Http\Controllers\Psb;

use App\Enums\PsbStatus;
use App\Http\Controllers\Controller;
use App\Models\PsbOrder;
use App\Services\PsbStateMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $counts = PsbOrder::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $allStatuses = PsbStatus::cases();
        $statusCounts = [];
        foreach ($allStatuses as $s) {
            $statusCounts[$s->value] = $counts[$s->value] ?? 0;
        }

        $recentOrders = PsbOrder::query()
            ->with(['teknisi'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($o) => [
                'id'              => $o->id,
                'customer_name'   => $o->customer_name,
                'status'          => $o->status->value,
                'status_label'    => $o->status->label(),
                'status_color'    => $o->status->color(),
                'coverage_status' => $o->coverage_status?->value,
                'olt_type'        => $o->olt_type?->value,
                'teknisi'         => $o->teknisi->pluck('name')->join(', '),
                'created_at'      => $o->created_at?->toDateTimeString(),
            ]);

        $todayStats = [
            'new_orders'        => PsbOrder::whereDate('created_at', today())->count(),
            'completed_today'   => PsbOrder::where('status', PsbStatus::Done)->whereDate('ebilling_synced_at', today())->count(),
            'pending_coverage'  => $statusCounts[PsbStatus::Submitted->value] ?? 0,
            'in_provisioning'   => $statusCounts[PsbStatus::Provisioning->value] ?? 0,
        ];

        return Inertia::render('Psb/Dashboard', [
            'statusCounts' => $statusCounts,
            'recentOrders' => $recentOrders,
            'todayStats'   => $todayStats,
        ]);
    }
}
