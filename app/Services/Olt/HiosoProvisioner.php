<?php

namespace App\Services\Olt;

use App\Models\PsbOrder;

/**
 * HiOS provisioner — MANUAL ONLY (jawaban #3: teknisi checklist).
 * Real provisioning terjadi di router pelanggan oleh teknisi.
 * Checklist items di psb_hioso_checklists yang tracking step-step manual.
 */
class HiosoProvisioner implements OltProvisionerInterface
{
    public function provision(PsbOrder $order, string $sn, string $port, ?string $onuId, string $name, string $password): array
    {
        return [
            'success' => true,
            'log'     => [
                'hioso_manual' => true,
                'instruction'  => "HiOS: teknisi input manual di router pelanggan. SN={$sn}, port={$port}, PPPoE user={$name}",
                'next_step'    => 'teknisi isi checklist di psb_hioso_checklists',
            ],
            'onu_id'  => $onuId,
            'error'   => null,
        ];
    }
}
