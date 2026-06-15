<?php

namespace App\Http\Controllers\Psb;

use App\Enums\PsbStatus;
use App\Http\Controllers\Controller;
use App\Models\PsbOrder;
use App\Services\PsbStateMachine;
use App\Services\SaleskitBridgeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PsbOrderController extends Controller
{
    public function __construct(
        private PsbStateMachine $sm,
        private SaleskitBridgeService $saleskit,
    ) {}

    public function index(Request $request): Response
    {
        $orders = PsbOrder::query()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where('customer_name', 'like', "%{$request->search}%"))
            ->with('teknisi')
            ->latest()
            ->paginate(20)
            ->through(fn ($o) => [
                'id'             => $o->id,
                'customer_name'  => $o->customer_name,
                'customer_phone' => $o->customer_phone,
                'village'        => $o->village,
                'package'        => $o->package,
                'status'         => $o->status->value,
                'status_label'   => $o->status->label(),
                'status_color'   => $o->status->color(),
                'olt_type'       => $o->olt_type?->value,
                'teknisi'        => $o->teknisi->pluck('name')->join(', '),
                'created_at'     => $o->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Psb/PsbOrders', [
            'orders' => $orders,
            'filters' => [
                'status' => $request->status,
                'search' => $request->search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Psb/PsbInput', [
            // Optional: list of recent registrations from Saleskit for quick pick
            'recentRegistrations' => $this->saleskit->getRegistration(0) ?? [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'registration_id' => 'required|string',
            'customer_name'   => 'required|string|max:255',
            'customer_phone'  => 'required|string|max:20',
            'customer_nik'    => 'nullable|string|max:20',
            'customer_email'  => 'nullable|email',
            'customer_address'=> 'required|string',
            'rt'              => 'required|string|max:5',
            'rw'              => 'required|string|max:5',
            'village'         => 'required|string',
            'district'        => 'required|string',
            'package'         => 'required|string',
            'router_name'     => 'required|string|max:50',
            'gps_lat'         => 'nullable|numeric|between:-90,90',     // bug #3 fix
            'gps_long'        => 'nullable|numeric|between:-180,180',   // bug #3 fix
        ]);

        $data['status']         = PsbStatus::Submitted;
        $data['sales_id']       = $request->user()->id;
        $data['coverage_status'] = 'pending';
        $data['provisioning_status'] = 'pending';

        $order = PsbOrder::create($data);

        // bug #2 fix: gak perlu transition() karena create() udah set status=Submitted
        // dan transition() pertama di log akan jadi "from null" — ini redundant.
        // Kita catat log manual dengan from=null untuk audit trail.
        \App\Models\PsbStatusLog::create([
            'psb_order_id' => $order->id,
            'from_status'  => null,
            'to_status'    => PsbStatus::Submitted->value,
            'note'         => 'PSB submitted by sales',
            'changed_by'   => $request->user()->id,
        ]);

        return redirect()->route('psb.orders.show', $order)->with('success', 'Order submitted');
    }

    public function show(PsbOrder $psbOrder): Response
    {
        $psbOrder->load(['teknisi', 'statusLogs.changedBy', 'hiosoChecklist.checkedBy']);
        return Inertia::render('Psb/PsbShow', [
            'order' => [
                ...$psbOrder->toArray(),
                'status_label'  => $psbOrder->status->label(),
                'status_color'  => $psbOrder->status->color(),
                'teknisi'       => $psbOrder->teknisi,
                'status_logs'   => $psbOrder->statusLogs,
                'hioso_checklist' => $psbOrder->hiosoChecklist,
            ],
        ]);
    }

    /**
     * Manual status transition (drag-drop Pipeline).
     * Hanya sales_leader / leader_teknisi / admin yang boleh.
     */
    public function transitionStatus(Request $request, PsbOrder $psbOrder)
    {
        $data = $request->validate([
            'status' => 'required|in:draft,submitted,coverage_ok,assigned,provisioning,photos,done,rejected',
            'note'   => 'nullable|string|max:500',
        ]);
        $target = \App\Enums\PsbStatus::from($data['status']);

        // Role check
        $user = $request->user();
        $allowed = match ($target) {
            // bug #4 fix: CoverageOk butuh sales_leader/admin (mereka yg approve coverage)
            \App\Enums\PsbStatus::Submitted,
            \App\Enums\PsbStatus::CoverageOk => $user->hasAnyRole(['sales_leader', 'admin']),
            // bug #5 fix: Rejected hanya sales_leader/leader_teknisi/admin — teknisi gak boleh reject
            \App\Enums\PsbStatus::Rejected  => $user->hasAnyRole(['sales_leader', 'leader_teknisi', 'admin']),
            \App\Enums\PsbStatus::Assigned  => $user->hasAnyRole(['leader_teknisi', 'admin']),
            \App\Enums\PsbStatus::Provisioning,
            \App\Enums\PsbStatus::Photos,
            \App\Enums\PsbStatus::Done       => $user->hasAnyRole(['teknisi', 'leader_teknisi', 'admin']),
            default                          => false,
        };
        if (! $allowed) {
            return back()->with('error', 'Anda tidak berhak transition ke status ini');
        }

        try {
            app(\App\Services\PsbStateMachine::class)
                ->transition($psbOrder, $target, $user, $data['note'] ?? "Drag-drop by {$user->name}");
            return back()->with('success', "Status updated ke {$target->label()}");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
