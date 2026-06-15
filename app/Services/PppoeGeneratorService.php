<?php

namespace App\Services;

use App\Models\PsbOrder;
use Illuminate\Support\Str;

/**
 * Generate PPPoE credentials.
 *
 * Username: {NAME}_RTxx_RWxx_ODP  (uppercase, no space)
 * Password: {nama_router}         (lowercase, BUKAN dari KTP — jawaban operator)
 *
 * Berlaku untuk C300 DAN HiOS (jawaban #10 di flowchart).
 */
class PppoeGeneratorService
{
    public function __construct(
        private ?string $prefix = null,
        private int $passwordLength = 10,
    ) {
        $this->prefix         = $this->prefix ?? config('psb.pppoe.prefix', 'ikr');
        $this->passwordLength = config('psb.pppoe.password_length', 10);
    }

    /**
     * Generate + persist PPPoE credentials to order.
     */
    public function generate(PsbOrder $order): array
    {
        $user = $this->buildUsername($order);
        $pass = $this->generatePassword();

        $order->update([
            'pppoe_user'          => $user,
            'pppoe_password'      => $pass,
            'pppoe_generated_at'  => now(),
        ]);

        return [
            'pppoe_user'     => $user,
            'pppoe_password' => $pass,
        ];
    }

    /**
     * Build username from customer name, RT, RW, ODP code.
     * Format: {NAME}_RTxx_RWxx_{ODP_SEGMENT}
     *
     * ODP segment rules (updated 2026-06-15):
     *   - Kalau odp_code punya format 'X.Y' (X.Y dipisah titik):
     *       pakai 'X.Y' as-is  →  ODP-GDG-1.1 → "1.1"
     *   - Kalau gak ada titik, ambil 2 digit terakhir dari semua digit, padded:
     *       ODP-MLG-001 → "01"
     *       MGL-005     → "05"
     *       5           → "05"
     *   - Fallback: "00"
     *
     * Note: PPPoE username boleh mengandung '.' — RADIUS/MikroTik accept.
     */
    public function buildUsername(PsbOrder $order): string
    {
        $name = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $order->customer_name ?? ''));
        $rt   = str_pad((string) ($order->rt ?? '00'), 2, '0', STR_PAD_LEFT);
        $rw   = str_pad((string) ($order->rw ?? '00'), 2, '0', STR_PAD_LEFT);
        $odp  = $this->extractOdpNumber($order->odp_code ?? null);
        return "{$name}_RT{$rt}_RW{$rw}_{$odp}";
    }

    /**
     * Extract ODP segment.
     *   Format "X.Y" (X.Y dipisah titik) → pakai as-is
     *     "ODP-GDG-1.1"  → "1.1"
     *     "ODP-GDG-1.10" → "1.10"
     *     "GDG-2.3"      → "2.3"
     *   Tanpa titik → ambil 2 digit terakhir dari semua digit, padded
     *     "ODP-MLG-001"  → "01"
     *     "MGL-005"      → "05"
     *     "012"          → "12"
     *     "5"            → "05"
     *   Fallback: "00"
     */
    public function extractOdpNumber(?string $odpCode): string
    {
        if (empty($odpCode)) {
            return '00';
        }

        // Rule 1: ada format X.Y (digit.digit) → pakai as-is segment terakhir
        // Tangkap pola: trailing atau anywhere "D+.D+" (digit.digit)
        if (preg_match('/(\d+\.\d+)/', $odpCode, $m)) {
            return $m[1];
        }

        // Rule 2: gak ada titik → 2 digit terakhir dari digit, padded
        $digits = preg_replace('/[^0-9]/', '', $odpCode);
        if (empty($digits)) {
            return '00';
        }
        $tail = strlen($digits) >= 2
            ? substr($digits, -2)
            : str_pad($digits, 2, '0', STR_PAD_LEFT);
        return $tail;
    }

    /**
     * Password = nama_router lowercase (BUKAN dari KTP).
     * Contoh: router "Mangliawan" → pass "mangliawan".
     */
    public function generatePassword(PsbOrder $order = null): string
    {
        if ($order && $order->router_name) {
            return strtolower(preg_replace('/[^A-Za-z0-9]/', '', $order->router_name));
        }
        // Fallback random (mis. router_name belum di-set)
        return Str::lower(Str::random($this->passwordLength));
    }
}
