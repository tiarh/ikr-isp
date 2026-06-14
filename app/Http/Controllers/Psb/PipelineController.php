<?php

namespace App\Http\Controllers\Psb;

use App\Enums\PsbStatus;
use App\Http\Controllers\Controller;
use App\Models\PsbOrder;
use Inertia\Inertia;
use Inertia\Response;

class PipelineController extends Controller
{
    public function __invoke(): Response
    {
        $orders = PsbOrder::with('teknisi')
            ->whereIn('status', [
                PsbStatus::Draft, PsbStatus::Submitted, PsbStatus::CoverageOk,
                PsbStatus::Assigned, PsbStatus::Provisioning, PsbStatus::Photos, PsbStatus::Done,
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($o) => [
                'id'             => $o->id,
                'customer_name'  => $o->customer_name,
                'customer_address' => $o->customer_address,
                'village'        => $o->village,
                'package'        => $o->package,
                'status'         => $o->status->value,
                'status_label'   => $o->status->label(),
                'olt_type'       => $o->olt_type?->value,
                'teknisi'        => $o->teknisi->pluck('name')->join(', '),
                'created_at'     => $o->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Psb/Pipeline', [
            'orders' => $orders,
        ]);
    }
}
