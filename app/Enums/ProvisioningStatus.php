<?php

namespace App\Enums;

enum ProvisioningStatus: string
{
    case Pending  = 'pending';
    case Running  = 'running';
    case Done     = 'done';
    case Failed   = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Belum',
            self::Running  => 'Berjalan',
            self::Done     => 'Selesai',
            self::Failed   => 'Gagal',
        };
    }
}
