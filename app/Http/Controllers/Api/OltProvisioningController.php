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
