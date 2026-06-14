<?php

namespace App\Enums;

/**
 * Paket internet yang dijual.
 * Sumber kebenaran = eBilling (customer_packages).
 * Disimpan sebagai string di psb_orders.package (denormalized).
 */
enum PackageSpeed: string
{
    case S10  = '10M';
    case S15  = '15M';
    case S25  = '25M';
    case S30  = '30M';
    case S35  = '35M';
    case S50  = '50M';
    case S100 = '100M';
    case S200 = '200M';

    public function mbps(): int
    {
        return match ($this) {
            self::S10  => 10,
            self::S15  => 15,
            self::S25  => 25,
            self::S30  => 30,
            self::S35  => 35,
            self::S50  => 50,
            self::S100 => 100,
            self::S200 => 200,
        };
    }

    public static function options(): array
    {
        return array_column(self::cases(), 'value');
    }
}
