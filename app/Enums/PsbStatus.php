<?php

namespace App\Enums;

/**
 * Status state machine PSB.
 * Final state bisa 'done' (sukses) atau 'rejected' (gagal).
 *
 * Reject flow (jawaban #6): reject → balik ke 'provisioning' (bukan ke 'submitted').
 */
enum PsbStatus: string
{
    case Draft        = 'draft';
    case Submitted    = 'submitted';
    case CoverageOk   = 'coverage_ok';
    case Assigned     = 'assigned';
    case Provisioning = 'provisioning';
    case Photos       = 'photos';
    case Done         = 'done';
    case Rejected     = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft        => 'Draft',
            self::Submitted    => 'Diajukan',
            self::CoverageOk   => 'Coverage OK',
            self::Assigned     => 'Ditugaskan',
            self::Provisioning => 'Provisioning',
            self::Photos       => 'Dokumentasi',
            self::Done         => 'Selesai',
            self::Rejected     => 'Ditolak / Revisi',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft        => 'gray',
            self::Submitted    => 'blue',
            self::CoverageOk   => 'cyan',
            self::Assigned     => 'yellow',
            self::Provisioning => 'orange',
            self::Photos       => 'purple',
            self::Done         => 'emerald',
            self::Rejected     => 'red',
        };
    }

    public function bgClass(): string
    {
        return "bg-status-{$this->value} text-white";
    }

    /** Bisa transition ke status ini? */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft        => in_array($target, [self::Submitted, self::Rejected]),
            self::Submitted    => in_array($target, [self::CoverageOk, self::Rejected]),
            self::CoverageOk   => in_array($target, [self::Assigned, self::Rejected]),
            self::Assigned     => in_array($target, [self::Provisioning, self::Rejected]),
            self::Provisioning => in_array($target, [self::Photos, self::Rejected]),
            // Reject flow jawaban #6: rejected → balik ke provisioning
            self::Rejected     => $target === self::Provisioning,
            // bug #R-fix: tambah Rejected dari Photos (teknisi bisa reject jika foto salah / BAI invalid)
            self::Photos       => in_array($target, [self::Done, self::Provisioning, self::Rejected]),
            self::Done         => false, // terminal
        };
    }

    public static function options(): array
    {
        return array_column(self::cases(), 'value');
    }
}
