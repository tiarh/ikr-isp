<?php

namespace App\Http\Controllers\Psb;

use App\Enums\PsbStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SyncOrderToEbilling;
use App\Models\PsbOrder;
use App\Services\EbillingBridgeService;
use App\Services\PsbStateMachine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SyncController extends Controller
{
    public function __construct(
        private EbillingBridgeService $bridge,
        private PsbStateMachine $sm,
    ) {}

    public function index(PsbOrder $psbOrder): Response
    {
        return Inertia::render('Psb/Sync', [
            'order' => $psbOrder,
            'preview' => $this->buildPreview($psbOrder),
        ]);
    }

    public function preview(PsbOrder $psbOrder): Response
    {
        return Inertia::render('Psb/Sync', [
            'order'   => $psbOrder,
            'preview' => $this->buildPreview($psbOrder),
        ]);
    }

    public function sync(Request $request, PsbOrder $psbOrder): RedirectResponse
    {
        // Pre-check: semua foto + BAI harus sudah ada
        if (! $psbOrder->isAllPhotosUploaded()) {
            return back()->with('error', 'Semua 6 foto harus di-upload dulu');
        }
        if (! $psbOrder->bai_signed_at) {
            return back()->with('error', 'BAI harus di-sign dulu');
        }
        if ($psbOrder->olt_type?->value === 'hioso' && ! $psbOrder->isAllHiOSChecklistDone()) {
            return back()->with('error', 'HiOS checklist harus lengkap');
        }

        // bug #8 fix: validate file ada di storage, gak cuma DB path
        $missing = $this->findMissingStorageFiles($psbOrder);
        if (! empty($missing)) {
            return back()->with('error', 'File hilang di storage: ' . implode(', ', $missing));
        }

        // Dispatch job (async) atau sync kalau queue gak ada
        if (config('queue.default') === 'sync') {
            $result = $this->bridge->fullSync($psbOrder);
            if (! $result['success']) {
                return back()->with('error', 'Sync gagal: ' . json_encode($result['log']));
            }
        } else {
            SyncOrderToEbilling::dispatch($psbOrder->id);
            return back()->with('success', 'Sync dispatched ke queue');
        }

        $this->sm->transition($psbOrder, PsbStatus::Done, $request->user(), 'Synced to eBilling');
        return back()->with('success', 'Synced to eBilling, status = done');
    }

    private function buildPreview(PsbOrder $o): array
    {
        return [
            'customer' => [
                'code'      => 'CUST-' . str_pad($o->id, 6, '0', STR_PAD_LEFT),
                'name'      => $o->customer_name,
                'phone'     => $o->customer_phone,
                'address'   => $o->customer_address,
                'village'   => $o->village,
                'district'  => $o->district,
                'package'   => $o->package,
                'router'    => $o->router_name,
            ],
            'pppoe' => [
                'user'     => $o->pppoe_user,
                'password' => $o->pppoe_password,
            ],
            'olt' => [
                'type'  => $o->olt_type?->value,
                'id'    => $o->olt_id,
                'port'  => $o->olt_port_label,
                'sn'    => $o->onu_serial,
            ],
            'files_uploaded' => [
                'foto_rumah'     => (bool) $o->foto_rumah_path,
                'foto_modem'     => (bool) $o->foto_modem_path,
                'foto_ktp'       => (bool) $o->foto_ktp_path,
                'foto_odp'       => (bool) $o->foto_odp_path,
                'foto_odp_dalam' => (bool) $o->foto_odp_dalam_path,
                'foto_router'    => (bool) $o->foto_router_path,
                'bai_pdf'        => (bool) $o->bai_pdf_path,
            ],
        ];
    }

    /**
     * bug #8 fix: cek file di storage, bukan cuma path di DB
     * Return list of missing files (filename only) for error message.
     */
    private function findMissingStorageFiles(PsbOrder $o): array
    {
        $disk = \Storage::disk('public');
        $paths = [
            'foto_rumah'      => $o->foto_rumah_path,
            'foto_modem'      => $o->foto_modem_path,
            'foto_ktp'        => $o->foto_ktp_path,
            'foto_odp'        => $o->foto_odp_path,
            'foto_odp_dalam'  => $o->foto_odp_dalam_path,
            'foto_router'     => $o->foto_router_path,
            'bai_pdf'         => $o->bai_pdf_path,
        ];
        $missing = [];
        foreach ($paths as $label => $path) {
            if ($path && ! $disk->exists($path)) {
                $missing[] = $label;
            }
        }
        return $missing;
    }
}
