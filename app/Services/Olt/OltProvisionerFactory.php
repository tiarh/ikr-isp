<?php

namespace App\Services\Olt;

use App\Enums\OltType;

/**
 * Factory pilih provisioner berdasarkan OltType.
 */
class OltProvisionerFactory
{
    public static function make(OltType $type): OltProvisionerInterface
    {
        return match ($type) {
            OltType::C300  => new C300Provisioner(),
            OltType::Hioso => new HiosoProvisioner(),
        };
    }
}
