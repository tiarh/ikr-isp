<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PsbOrder;
use App\Services\EbillingBridgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API untuk eBilling panggil — sinkronisasi order dari IKR-ISP ke eBilling.
 */
class EbillingSyncController extends Controller
{
    public function __construct(private EbillingBridgeService $bridge) {}

    public function sync(Request $request, PsbOrder $psb_order): JsonResponse
    {
        $result = $this->bridge->fullSync($psb_order);
        return response()->json($result);
    }

    public function status(PsbOrder $psb_order): JsonResponse
    {
        return response()->json([
            'order_id'           => $psb_order->id,
            'status'             => $psb_order->status->value,
            'provisioning_status'=> $psb_order->provisioning_status?->value,
            'ebilling_customer_id' => $psb_order->ebilling_customer_id,
            'ebilling_synced_at' => $psb_order->ebilling_synced_at?->toIso8601String(),
            'last_log'           => $psb_order->ebilling_sync_log,
        ]);
    }
}
