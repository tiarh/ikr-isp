<?php

namespace App\Jobs;

use App\Services\EbillingBridgeService;
use App\Models\PsbOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncOrderToEbilling implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $psbOrderId) {}

    public function handle(EbillingBridgeService $bridge): void
    {
        $order = PsbOrder::find($this->psbOrderId);
        if (! $order) {
            Log::warning('SyncOrderToEbilling: order not found', ['id' => $this->psbOrderId]);
            return;
        }
        $result = $bridge->fullSync($order);
        Log::info('eBilling fullSync result', [
            'order_id' => $this->psbOrderId,
            'success'  => $result['success'],
        ]);
    }
}
