<?php

namespace App\Services\Olt;

use App\Enums\OltType;
use App\Models\PsbOrder;

interface OltProvisionerInterface
{
    /**
     * @return array{success:bool, log:array, onu_id:?string, error:?string}
     */
    public function provision(PsbOrder $order, string $sn, string $port, ?string $onuId, string $name, string $password): array;
}
