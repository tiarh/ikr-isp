<?php

namespace App\Http\Controllers\Psb;

use App\Enums\OltType;
use App\Enums\PsbStatus;
use App\Http\Controllers\Controller;
use App\Models\PsbOrder;
use App\Services\OltService;
use App\Services\PppoeGeneratorService;
use App\Services\PsbStateMachine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProvisioningController extends Controller
{
    public function __construct(
        private PppoeGeneratorService $pppoe,
        private PsbStateMachine $sm,
    ) {}

    public function index(): Response
    {
        $orders = PsbOrder::whereIn('status', [
            PsbStatus::Assigned, PsbStatus::Provisioning,
        ])
            ->with(['teknisi', 'hiosoChecklist'])
            ->latest()
            ->get();

        return Inertia::render('Psb/Provisioning', [
            'orders' => $orders,
        ]);
    }

    public function selectOlt(Request $request, PsbOrder $psbOrder): RedirectResponse
    {
        $data = $request->validate([
            'olt_type'     => 'required|in:c300,hioso',
            'olt_id'       => 'required|integer',
            'onu_serial'   => 'required|string|max:100',
            'olt_port'     => 'required|string|max:50',
        ]);

        $psbOrder->update([
            'olt_type'       => $data['olt_type'],
            'olt_id'         => $data['olt_id'],
            'onu_serial'     => $data['onu_serial'],
            'olt_port_label' => $data['olt_port'],
        ]);

        // Generate PPPoE
        $this->pppoe->generate($psbOrder);

        // Move to provisioning
        if ($psbOrder->status === PsbStatus::Assigned) {
            $this->sm->transition($psbOrder, PsbStatus::Provisioning, $request->user(), 'OLT selected, PPPoE generated');
        }

        return back()->with('success', "OLT selected, PPPoE generated: {$psbOrder->pppoe_user}");
    }

    public function provision(Request $request, PsbOrder $psbOrder): RedirectResponse
    {
        $psbOrder->update(['provisioning_status' => 'running']);

        $provisioner = OltProvisionerFactory::make($psbOrder->olt_type);
        $result = $provisioner->provision(
            $psbOrder,
            $psbOrder->onu_serial,
            $psbOrder->olt_port_label,
            null,
            $psbOrder->pppoe_user,
            $psbOrder->pppoe_password,
        );

        $psbOrder->update([
            'provisioning_status' => $result['success'] ? 'done' : 'failed',
            'provisioning_log'    => $result['log'],
            'provisioned_at'      => $result['success'] ? now() : null,
        ]);

        if (! $result['success']) {
            return back()->with('error', 'Provisioning failed: ' . ($result['error'] ?? 'unknown'));
        }

        return back()->with('success', 'Provisioning completed');
    }
}
