<?php

namespace App\Services;

use App\Enums\PsbStatus;
use App\Jobs\SendWaNotification;
use App\Models\PsbOrder;
use App\Models\PsbStatusLog;
use App\Models\User;
use App\Services\WaNotificationService;

/**
 * Centralized PSB state machine service.
 * Handle transition, logging, WA notif, side effects.
 */
class PsbStateMachine
{
    public function __construct(private WaNotificationService $wa) {}

    public function transition(
        PsbOrder $order,
        PsbStatus $to,
        ?User $user = null,
        ?string $note = null,
        array $meta = [],
    ): PsbOrder {
        $from = $order->status;

        if (! $from->canTransitionTo($to)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$from->value} to {$to->value}"
            );
        }

        // If transitioning to Rejected, save previous status for revert (jawaban #6)
        if ($to === PsbStatus::Rejected) {
            $order->previous_status = $from->value;
        }

        $order->status = $to;
        $order->save();

        // Log
        PsbStatusLog::create([
            'psb_order_id' => $order->id,
            'from_status'  => $from->value,
            'to_status'    => $to->value,
            'note'         => $note,
            'changed_by'   => $user?->id,
            'meta'         => $meta,
        ]);

        // Auto-create HiOS checklist on first transition to provisioning
        if ($to === PsbStatus::Provisioning
            && $order->olt_type?->value === 'hioso'
            && $order->hiosoChecklist()->count() === 0
        ) {
            foreach (\App\Models\PsbHiosoChecklist::defaultItems() as $item) {
                \App\Models\PsbHiosoChecklist::create(array_merge(
                    ['psb_order_id' => $order->id],
                    $item,
                ));
            }
        }

        // Auto-transition ke photos jika semua foto sudah uploaded saat ini di provisioning
        // (handled by PsbDocumentController::uploadPhoto() biar user-triggered)

        // WA notif
        $this->wa->notifyStatusChange($order, $from, $to, $user);

        return $order->fresh();
    }

    public function revertFromRejected(PsbOrder $order, ?User $user = null, ?string $note = null): PsbOrder
    {
        if ($order->status !== PsbStatus::Rejected) {
            throw new \InvalidArgumentException('Order is not in rejected status');
        }
        $previous = $order->previous_status
            ? PsbStatus::from($order->previous_status)
            : PsbStatus::Provisioning;

        $order->previous_status = null;
        $order->revision_note   = null;
        $order->save();

        return $this->transition($order, $previous, $user, $note ?: 'Reverted from rejected');
    }
}
