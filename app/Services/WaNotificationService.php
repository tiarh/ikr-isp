<?php

namespace App\Services;

use App\Enums\PsbStatus;
use App\Models\PsbOrder;
use App\Models\PsbStatusLog;
use App\Models\User;
use App\Models\WaNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WhatsApp notifier via evolution-api.
 *
 * Trigger: setiap PsbStatus transition (jawaban #9).
 * Outbox pattern: insert ke wa_notifications table, queue worker kirim.
 */
class WaNotificationService
{
    public function notifyStatusChange(PsbOrder $order, PsbStatus $from, PsbStatus $to, ?User $changedBy = null): void
    {
        if (! config('psb.evolution.api_url') || ! config('psb.evolution.api_key')) {
            // Evolution API not configured — silently skip (jangan spam log)
            return;
        }

        $messages = $this->buildMessages($order, $from, $to, $changedBy);
        if (empty($messages)) {
            return;
        }

        foreach ($messages as $msg) {
            try {
                WaNotification::create([
                    'notifiable_type' => 'psb_order',
                    'notifiable_id'   => $order->id,
                    'channel'         => $msg['channel'],
                    'recipient'       => $msg['recipient'],
                    'message'         => $msg['message'],
                    'payload'         => $msg['payload'] ?? null,
                    'status'          => 'pending',
                ]);
                \App\Jobs\SendWaNotification::dispatch($msg['recipient'], $msg['message']);
            } catch (\Throwable $e) {
                Log::error('WA notif enqueue failed', ['err' => $e->getMessage()]);
            }
        }
    }

    private function buildMessages(PsbOrder $order, PsbStatus $from, PsbStatus $to, ?User $changedBy): array
    {
        $customer = $order->customer_name;
        $orderNum = str_pad($order->id, 5, '0', STR_PAD_LEFT);
        $url = url("/psb/orders/{$order->id}");

        $messages = [];

        // Status-specific message
        $body = match ($to) {
            PsbStatus::Submitted => "📋 *PSB #{$orderNum}* DIAJUKAN\n👤 {$customer}\n📍 {$order->village}/{$order->district}\n⏳ Menunggu coverage check.",
            PsbStatus::CoverageOk => "✅ *PSB #{$orderNum}* COVERAGE OK\n👤 {$customer}\n📏 Jarak ke ODP: " . round($order->odp_distance_m) . "m\n⏭️ Menunggu assignment teknisi.",
            PsbStatus::Assigned   => "👷 *PSB #{$orderNum}* DITUGASKAN\n👤 {$customer}\n🔧 Teknisi: " . ($order->teknisi->pluck('name')->join(', ') ?: 'belum di-assign') . "\n📦 Paket: {$order->package}",
            PsbStatus::Provisioning => "⚙️ *PSB #{$orderNum}* PROVISIONING\n👤 {$customer}\n🔌 OLT: " . strtoupper($order->olt_type?->value ?? '-') . "\n🔑 PPPoE: {$order->pppoe_user}",
            PsbStatus::Photos     => "📷 *PSB #{$orderNum}* DOKUMENTASI\n👤 {$customer}\n🖼️ Teknisi sedang upload foto & BAI",
            PsbStatus::Done       => "🎉 *PSB #{$orderNum}* SELESAI\n👤 {$customer}\n📅 Tgl daftar: " . ($order->ebilling_synced_at?->format('d-m-Y') ?? '-') . "\n🔗 Detail: {$url}",
            PsbStatus::Rejected   => "⚠️ *PSB #{$orderNum}* DITOLAK / REVISI\n👤 {$customer}\n📝 Note: {$order->revision_note}",
            default               => "ℹ️ *PSB #{$orderNum}* status: {$to->label()}",
        };

        // Tentukan recipient
        $teknisiGroup = config('psb.evolution.wa_group_teknisi');
        $salesGroup   = config('psb.evolution.wa_group_sales');

        $teknisiStatus = [PsbStatus::Assigned, PsbStatus::Provisioning, PsbStatus::Photos, PsbStatus::Done];
        $salesStatus   = [PsbStatus::Submitted, PsbStatus::CoverageOk, PsbStatus::Rejected];

        if (in_array($to, $teknisiStatus) && $teknisiGroup) {
            $messages[] = [
                'channel'   => 'wa_group_teknisi',
                'recipient' => $teknisiGroup,
                'message'   => $body,
                'payload'   => ['order_id' => $order->id, 'status' => $to->value],
            ];
        }
        if (in_array($to, $salesStatus) && $salesGroup) {
            $messages[] = [
                'channel'   => 'wa_group_sales',
                'recipient' => $salesGroup,
                'message'   => $body,
                'payload'   => ['order_id' => $order->id, 'status' => $to->value],
            ];
        }

        return $messages;
    }
}
