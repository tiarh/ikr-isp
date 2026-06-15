<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PsbOrder;
use App\Services\OltService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OltProvisioningController extends Controller
{
    public function __construct(private OltService $oltService) {}

    public function provision(Request $request): JsonResponse
    {
        $data = $request->validate([
            'psb_order_id' => 'required|integer|exists:psb_orders,id',
            'sn'           => 'required|string',
            'port'         => 'required|string',
            'onu_id'       => 'nullable|string',
            'name'         => 'required|string',
            'password'     => 'required|string',
        ]);

        $order = PsbOrder::findOrFail($data['psb_order_id']);

        // bug #9 fix: ensure OLT type is set (kalau null, factory->make() bakal error)
        if ($order->olt_type === null) {
            return response()->json([
                'success' => false,
                'error'   => 'OLT type not set for this order (pilih OLT dulu di step Provisioning)',
            ], 422);
        }

        // bug #9 fix: validate name/password matches order's PPPoE credentials
        // (mencegah teknisi nge-provision dgn creds orang lain)
        if ($order->pppoe_user && $data['name'] !== $order->pppoe_user) {
            return response()->json([
                'success' => false,
                'error'   => 'PPPoE username mismatch dengan order (expected: ' . $order->pppoe_user . ')',
            ], 422);
        }
        if ($order->pppoe_password && $data['password'] !== $order->pppoe_password) {
            return response()->json([
                'success' => false,
                'error'   => 'PPPoE password mismatch dengan order',
            ], 422);
        }

        $provisioner = OltProvisionerFactory::make($order->olt_type);
        $result = $provisioner->provision(
            $order,
            $data['sn'],
            $data['port'],
            $data['onu_id'] ?? null,
            $data['name'],
            $data['password'],
        );

        return response()->json($result);
    }
}
