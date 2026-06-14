<?php

namespace App\Enums;

enum OltType: string
{
    case C300  = 'c300';
    case Hioso = 'hioso';

    public function label(): string
    {
        return match ($this) {
            self::C300  => 'ZTE C300',
            self::Hioso => 'HiOS / HiOS',
        };
    }

    public function requiresManualChecklist(): bool
    {
        return $this === self::Hioso;
    }

    public static function options(): array
    {
        return array_column(self::cases(), 'value');
    }
}
