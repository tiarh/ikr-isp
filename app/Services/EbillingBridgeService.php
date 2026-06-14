<?php

namespace App\Services;

use App\Enums\OltType;
use App\Enums\PsbStatus;
use App\Jobs\SendWaNotification;
use App\Models\PsbOrder;
use App\Models\User;
use App\Models\WaNotification;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bridge ke eBilling (customer sync, RADIUS, invoice).
 *
 * Final sync (setelah provisioning done + photos uploaded + BAI signed):
 *  1. POST /customers       (CUST-000xxx)
 *  2. POST /customers/{id}/invoice  (auto-gen, join_date = provisioned_at)
 *  3. POST /radius/accounts (radcheck+radreply)
 *  4. POST /mikrotik/secret (add PPPoE secret)
 *  5. Upload 6 foto + BAI PDF
 */
class EbillingBridgeService
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri'        => config('psb.ebilling.api_url'),
            'timeout'         => config('psb.ebilling.timeout', 30),
            'connect_timeout' => 10,
            'headers'         => [
                'Authorization' => 'Bearer ' . config('psb.ebilling.api_key'),
                'Accept'        => 'application/json',
            ],
        ]);
    }

    public function fullSync(PsbOrder $order): array
    {
        $log = [];
        $success = true;

        try {
            // 1. POST customer
            $customer = $this->syncCustomer($order);
            $log['customer'] = $customer;
            if (! ($customer['success'] ?? false)) {
                $success = false;
                $log['error'] = 'customer_sync_failed';
            } else {
                $ebillingCustomerId = $customer['data']['id'] ?? null;
                $order->update(['ebilling_customer_id' => $ebillingCustomerId]);

                // 2. invoice
                if ($ebillingCustomerId) {
                    $invoice = $this->syncInvoice($order, $ebillingCustomerId);
                    $log['invoice'] = $invoice;
                    if (! ($invoice['success'] ?? false)) {
                        $log['warning'] = 'invoice_sync_failed_but_customer_created';
                    }
                }

                // 3. RADIUS
                $radius = $this->syncRadius($order);
                $log['radius'] = $radius;

                // 4. MikroTik PPPoE
                $mt = $this->syncMikrotikSecret($order);
                $log['mikrotik'] = $mt;

                // 5. Upload files (6 foto + BAI PDF)
                $files = $this->uploadFiles($order, $ebillingCustomerId);
                $log['files'] = $files;
            }
        } catch (\Throwable $e) {
            Log::error('eBilling fullSync failed', ['err' => $e->getMessage()]);
            $success = false;
            $log['exception'] = $e->getMessage();
        }

        $order->update([
            'ebilling_synced_at' => $success ? now() : null,
            'ebilling_sync_log'  => $log,
        ]);

        return [
            'success' => $success,
            'log'     => $log,
        ];
    }

    public function syncCustomer(PsbOrder $order): array
    {
        try {
            $res = $this->http->post('/customers', [
                'json' => [
                    'code'           => 'CUST-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                    'name'           => $order->customer_name,
                    'phone'          => $order->customer_phone,
                    'email'          => $order->customer_email,
                    'nik'            => $order->customer_nik,
                    'address'        => $order->customer_address,
                    'village'        => $order->village,
                    'district'       => $order->district,
                    'rt'             => $order->rt,
                    'rw'             => $order->rw,
                    'pppoe_user'     => $order->pppoe_user,
                    'pppoe_password' => $order->pppoe_password,
                    'package'        => $order->package,
                    'router_name'    => $order->router_name,
                    'join_date'      => $order->provisioned_at?->format('Y-m-d'),
                    'olt_id'         => $order->olt_id,
                    'odp_id'         => $order->odp_asset_id,
                ],
            ]);
            return [
                'success' => true,
                'data'    => json_decode($res->getBody()->getContents(), true),
            ];
        } catch (\Throwable $e) {
            Log::error('eBilling syncCustomer failed', ['err' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncInvoice(PsbOrder $order, int $ebillingCustomerId): array
    {
        try {
            $res = $this->http->post("/customers/{$ebillingCustomerId}/invoice", [
                'json' => [
                    'amount'    => $this->resolvePackagePrice($order->package),
                    'due_date'  => now()->addDays(7)->format('Y-m-d'),
                    'notes'     => "Auto-generated from PSB #{$order->id}",
                ],
            ]);
            return [
                'success' => true,
                'data'    => json_decode($res->getBody()->getContents(), true),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncRadius(PsbOrder $order): array
    {
        try {
            $res = $this->http->post('/radius/accounts', [
                'json' => [
                    'username' => $order->pppoe_user,
                    'password' => $order->pppoe_password,
                    'groupname'=> $this->mapPackageToRadiusGroup($order->package),
                ],
            ]);
            return [
                'success' => true,
                'data'    => json_decode($res->getBody()->getContents(), true),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncMikrotikSecret(PsbOrder $order): array
    {
        try {
            $res = $this->http->post('/mikrotik/secret', [
                'json' => [
                    'name'     => $order->pppoe_user,
                    'password' => $order->pppoe_password,
                    'profile'  => $this->mapPackageToMikrotikProfile($order->package),
                    'service'  => 'pppoe',
                    'router'   => $order->router_name,
                ],
            ]);
            return [
                'success' => true,
                'data'    => json_decode($res->getBody()->getContents(), true),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function uploadFiles(PsbOrder $order, int $ebillingCustomerId): array
    {
        $files = [
            'foto_rumah'     => $order->foto_rumah_path,
            'foto_modem'     => $order->foto_modem_path,
            'foto_ktp'       => $order->foto_ktp_path,
            'foto_odp'       => $order->foto_odp_path,
            'foto_odp_dalam' => $order->foto_odp_dalam_path,
            'foto_router'    => $order->foto_router_path,
            'bai_pdf'        => $order->bai_pdf_path,
        ];

        $results = [];
        foreach ($files as $key => $path) {
            if (! $path || ! \Storage::disk('public')->exists($path)) {
                $results[$key] = ['success' => false, 'error' => 'file_not_found'];
                continue;
            }
            try {
                $res = $this->http->post("/customers/{$ebillingCustomerId}/files", [
                    'multipart' => [
                        ['name' => 'kind', 'contents' => $key],
                        [
                            'name'     => 'file',
                            'contents' => fopen(\Storage::disk('public')->path($path), 'r'),
                            'filename' => basename($path),
                        ],
                    ],
                ]);
                $results[$key] = [
                    'success' => true,
                    'data'    => json_decode($res->getBody()->getContents(), true),
                ];
            } catch (\Throwable $e) {
                $results[$key] = ['success' => false, 'error' => $e->getMessage()];
            }
        }
        return $results;
    }

    private function resolvePackagePrice(?string $package): int
    {
        // Stub — panggil eBilling /packages/{code} atau mapping lokal
        return match ($package) {
            '10M'  => 150000,
            '15M'  => 200000,
            '25M'  => 250000,
            '30M'  => 300000,
            '50M'  => 400000,
            '100M' => 600000,
            '200M' => 900000,
            default => 200000,
        };
    }

    private function mapPackageToRadiusGroup(?string $package): string
    {
        return match ($package) {
            '10M'  => '10M',
            '15M'  => '15M',
            '25M'  => '25M',
            '30M'  => '30M',
            '50M'  => '50M',
            '100M' => '100M',
            '200M' => '200M',
            default => 'default',
        };
    }

    private function mapPackageToMikrotikProfile(?string $package): string
    {
        return match ($package) {
            '10M'  => 'paket-10M',
            '15M'  => 'paket-15M',
            '25M'  => 'paket-25M',
            '30M'  => 'paket-30M',
            '50M'  => 'paket-50M',
            '100M' => 'paket-100M',
            '200M' => 'paket-200M',
            default => 'paket-default',
        };
    }
}
