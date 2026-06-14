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
     * Format: {NAME}_RTxx_RWxx_ODP
     */
    public function buildUsername(PsbOrder $order): string
    {
        $name = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $order->customer_name ?? ''));
        $rt   = str_pad((string) ($order->rt ?? '00'), 2, '0', STR_PAD_LEFT);
        $rw   = str_pad((string) ($order->rw ?? '00'), 2, '0', STR_PAD_LEFT);
        $odp  = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $order->odp_code ?? 'ODP'));
        return "{$name}_RT{$rt}_RW{$rw}_{$odp}";
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
