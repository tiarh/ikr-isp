<?php

namespace App\Http\Controllers\Psb;

use App\Enums\PsbStatus;
use App\Http\Controllers\Controller;
use App\Models\PsbOrder;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_orders'        => PsbOrder::count(),
            'draft'               => PsbOrder::where('status', PsbStatus::Draft)->count(),
            'submitted'           => PsbOrder::where('status', PsbStatus::Submitted)->count(),
            'coverage_ok'         => PsbOrder::where('status', PsbStatus::CoverageOk)->count(),
            'assigned'            => PsbOrder::where('status', PsbStatus::Assigned)->count(),
            'provisioning'        => PsbOrder::where('status', PsbStatus::Provisioning)->count(),
            'photos'              => PsbOrder::where('status', PsbStatus::Photos)->count(),
            'done'                => PsbOrder::where('status', PsbStatus::Done)->count(),
            'rejected'            => PsbOrder::where('status', PsbStatus::Rejected)->count(),
            'today'               => PsbOrder::whereDate('created_at', today())->count(),
            'avg_completion_days' => PsbOrder::whereNotNull('ebilling_synced_at')
                ->selectRaw('AVG(DATEDIFF(ebilling_synced_at, created_at)) as avg_days')
                ->value('avg_days'),
        ];

        $byPackage = PsbOrder::query()
            ->selectRaw('package, COUNT(*) as total')
            ->whereNotNull('package')
            ->groupBy('package')
            ->pluck('total', 'package')
            ->toArray();

        $byTeknisi = PsbOrder::query()
            ->with('teknisi')
            ->where('status', PsbStatus::Done)
            ->get()
            ->flatMap(fn ($o) => $o->teknisi->pluck('name'))
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->toArray();

        return Inertia::render('Psb/Reports', [
            'stats'      => $stats,
            'byPackage'  => $byPackage,
            'byTeknisi'  => $byTeknisi,
        ]);
    }

    public function exportCsv(): BinaryFileResponse
    {
        return Excel::download(
            new \App\Exports\PsbOrdersExport(),
            'psb-orders-' . now()->format('Y-m-d') . '.xlsx',
        );
    }
}
