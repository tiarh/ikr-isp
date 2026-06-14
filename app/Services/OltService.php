<?php

namespace App\Services;

use App\Models\PsbOrder;
use App\Services\Olt\OltProvisionerFactory;

/**
 * OLT provisioning service — thin wrapper over OltProvisionerFactory.
 *
 * Kept for backward compatibility with controllers that inject this class.
 * The real implementation lives in App\Services\Olt\*.
 */
class OltService
{
    /**
     * Run provisioning for a PSB order. Delegates to the correct provisioner
     * (C300 or HiOS) based on the order's OltType.
     *
     * @return array{success:bool, log:array, onu_id:?string, error:?string}
     */
    public function provision(
        PsbOrder $order,
        string $sn,
        string $port,
        ?string $onuId,
        string $name,
        string $password
    ): array {
        $factory = new OltProvisionerFactory();
        $provisioner = $factory->make($order->olt_type);
        return $provisioner->provision($order, $sn, $port, $onuId, $name, $password);
    }
}
