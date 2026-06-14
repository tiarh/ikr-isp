<?php

namespace App\Observers;

use App\Enums\PsbStatus;
use App\Models\PsbHiosoChecklist;
use App\Models\PsbOrder;
use App\Services\PsbStateMachine;

class PsbOrderObserver
{
    public function __construct(private PsbStateMachine $sm) {}

    public function updated(PsbOrder $order): void
    {
        // Auto-create HiOS checklist items saat first transition ke provisioning
        if ($order->isDirty('status')
            && $order->status === PsbStatus::Provisioning
            && $order->olt_type
            && $order->olt_type->value === 'hioso'
            && $order->hiosoChecklist()->count() === 0
        ) {
            foreach (PsbHiosoChecklist::defaultItems() as $item) {
                PsbHiosoChecklist::create(array_merge(
                    ['psb_order_id' => $order->id],
                    $item,
                ));
            }
        }
    }
}
