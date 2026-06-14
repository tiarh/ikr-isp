<?php

namespace App\Services\Olt;

use App\Models\PsbOrder;
use phpseclib3\Net\SSH2;
use Illuminate\Support\Facades\Log;

/**
 * ZTE C300 provisioner — SSH auto via phpseclib3.
 *
 * NOTE: Command-command di bawah ini adalah TEMPLATE yang perlu disesuaikan
 * dengan firmware ZTE C300 aktual di lapangan. Selalu test di OLT non-prod dulu.
 *
 * Workflow (sesuai jawaban #4: beda work flow dgn HiOS):
 *  1. detect ONU by serial number
 *  2. register ONU di PON port
 *  3. create service-port + VLAN
 *  4. create PPPoE WAN profile
 */
class C300Provisioner implements OltProvisionerInterface
{
    private string $host;
    private string $username;
    private string $password;
    private int $timeout;
    private int $promptWait;
    private int $vlan;

    public function __construct()
    {
        $this->host       = config('psb.olt.c300.host', '192.168.1.1');
        $this->username   = config('psb.olt.c300.user', 'admin');
        $this->password   = config('psb.olt.c300.password', '');
        $this->timeout    = config('psb.olt.c300.timeout', 30);
        $this->promptWait = config('psb.olt.c300.prompt_wait', 3);
        $this->vlan       = (int) env('OLT_C300_VLAN', 100);
    }

    public function provision(PsbOrder $order, string $sn, string $port, ?string $onuId, string $name, string $password): array
    {
        $log = [];
        $onuId = $onuId ?: '1'; // default; production: query OLT for next free ID

        try {
            $ssh = new SSH2($this->host, 22, 10);
            $ssh->setTimeout($this->timeout);

            if (! $ssh->login($this->username, $this->password)) {
                return [
                    'success' => false,
                    'log'     => ['login_failed'],
                    'onu_id'  => null,
                    'error'   => "SSH login failed to C300 ({$this->host})",
                ];
            }

            $log[] = "Logged in to C300 ({$this->host})";
            $ssh->write("enable\nconfigure terminal\n");
            sleep(1);

            // 1. detect ONU
            $ssh->write("show gpon onu by sn {$sn}\n");
            sleep($this->promptWait);
            $detectOut = $ssh->read();
            $log[] = "detect ONU SN={$sn}";

            if (stripos($detectOut, $sn) === false) {
                $ssh->exec('end');
                $ssh->disconnect();
                return [
                    'success' => false,
                    'log'     => $log,
                    'onu_id'  => null,
                    'error'   => "ONU with SN {$sn} not detected on OLT port {$port}. Check kabel fiber & ODP.",
                ];
            }

            // 2. register ONU
            $ssh->write("interface gpon {$port}\n");
            sleep(1);
            $ssh->write("onu {$onuId} sn {$sn}\n");
            sleep(2);
            $log[] = "ONU {$onuId} registered on port {$port}";

            // 3. exit interface, ensure VLAN
            $ssh->write("exit\n");
            $ssh->write("vlan {$this->vlan}\n");
            sleep(1);
            $log[] = "VLAN {$this->vlan} ensured";

            // 4. service-port
            $ssh->write("service-port {$port} vport {$onuId} user-vlan {$this->vlan} vlan {$this->vlan}\n");
            sleep(1);
            $log[] = "Service-port bound";

            // 5. PPPoE WAN profile
            $ssh->write("pon-onu-mng {$port}:{$onuId}\n");
            sleep(1);
            $ssh->write("wan-ip 1 mode pppoe username {$name} password {$password}\n");
            sleep(1);
            $ssh->write("wan-ip 1 nat enable\n");
            sleep(1);
            $ssh->write("wan-ip 1 vlan {$this->vlan} tag-mode tag\n");
            sleep(1);
            $ssh->write("exit\n");
            $ssh->write("end\n");
            $ssh->write("write\n");
            sleep(2);
            $log[] = "PPPoE WAN applied: user={$name}";

            $ssh->disconnect();

            return [
                'success' => true,
                'log'     => $log,
                'onu_id'  => $onuId,
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::error('C300 provision failed', [
                'sn'  => $sn,
                'err' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'log'     => $log,
                'onu_id'  => null,
                'error'   => $e->getMessage(),
            ];
        }
    }
}
