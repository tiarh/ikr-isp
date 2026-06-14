<?php

namespace App\Models;

use App\Enums\CoverageStatus;
use App\Enums\OltType;
use App\Enums\PackageSpeed;
use App\Enums\ProvisioningStatus;
use App\Enums\PsbStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PsbOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'psb_orders';

    protected $fillable = [
        'registration_id',
        'sales_id', 'sales_leader_id', 'leader_teknisi_id', 'primary_teknisi_id',
        'area_id', 'router_id', 'olt_id', 'odp_asset_id',
        'odp_code', 'odp_lat', 'odp_lng', 'odp_distance_m', 'coverage_status', 'olt_type',
        'olt_port_label', 'onu_serial',
        'customer_name', 'customer_phone', 'customer_nik', 'customer_email',
        'customer_address', 'rt', 'rw', 'village', 'district', 'package',
        'router_name', 'pppoe_user', 'pppoe_password', 'pppoe_generated_at',
        'provisioning_status', 'provisioning_log', 'provisioned_at',
        'foto_rumah_path', 'foto_modem_path', 'foto_ktp_path',
        'foto_odp_path', 'foto_odp_dalam_path', 'foto_router_path',
        'redaman_odp', 'redaman_router', 'gps_lat', 'gps_long',
        'bai_pdf_path', 'bai_signed_at',
        'previous_status', 'revision_note',
        'status',
        'ebilling_customer_id', 'ebilling_synced_at', 'ebilling_sync_log',
    ];

    protected $casts = [
        'odp_lat'         => 'float',
        'odp_lng'         => 'float',
        'odp_distance_m'  => 'float',
        'gps_lat'         => 'float',
        'gps_long'        => 'float',
        'redaman_odp'      => 'float',
        'redaman_router'   => 'float',
        'pppoe_generated_at' => 'datetime',
        'provisioned_at'   => 'datetime',
        'bai_signed_at'    => 'datetime',
        'ebilling_synced_at' => 'datetime',
        'provisioning_log' => 'array',
        'ebilling_sync_log'=> 'array',
        'status'           => PsbStatus::class,
        'coverage_status'  => CoverageStatus::class,
        'olt_type'         => OltType::class,
        'provisioning_status' => ProvisioningStatus::class,
    ];

    protected $appends = ['status_label', 'status_color', 'is_multi_teknisi'];

    // ── Accessors ─────────────────────────────────────────────
    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '-';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status?->color() ?? 'gray';
    }

    public function getIsMultiTeknisiAttribute(): bool
    {
        return $this->teknisi()->count() > 1;
    }

    // ── Relations ─────────────────────────────────────────────
    public function teknisi(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'psb_order_teknisi', 'psb_order_id', 'teknisi_id')
            ->withPivot('role', 'assigned_at', 'completed_at', 'notes')
            ->withTimestamps();
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PsbStatusLog::class)->orderBy('created_at', 'desc');
    }

    public function hiosoChecklist(): HasMany
    {
        return $this->hasMany(PsbHiosoChecklist::class)->orderBy('sort_order');
    }

    public function isAllHiOSChecklistDone(): bool
    {
        if ($this->olt_type !== OltType::Hioso) {
            return true;
        }
        return $this->hiosoChecklist()->where('is_checked', false)->count() === 0;
    }

    public function isAllPhotosUploaded(): bool
    {
        return $this->foto_rumah_path
            && $this->foto_modem_path
            && $this->foto_ktp_path
            && $this->foto_odp_path
            && $this->foto_odp_dalam_path
            && $this->foto_router_path;
    }

    // ── Static helpers ────────────────────────────────────────
    public static function generatePppoeUser(string $customerName, string $rt, string $rw, string $odpCode): string
    {
        $name = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $customerName));
        $rt   = str_pad($rt, 2, '0', STR_PAD_LEFT);
        $rw   = str_pad($rw, 2, '0', STR_PAD_LEFT);
        $odp  = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $odpCode));
        return "{$name}_RT{$rt}_RW{$rw}_{$odp}";
    }

    public static function generatePppoePassword(string $routerName): string
    {
        return strtolower(preg_replace('/[^A-Za-z0-9]/', '', $routerName));
    }

    public function calculateOdDistance(float $lat, float $lng): float
    {
        // Haversine
        if (! $this->odp_lat || ! $this->odp_lng) {
            return PHP_FLOAT_MAX;
        }
        $earthRadius = 6371000;
        $dLat = deg2rad((float) $this->odp_lat - $lat);
        $dLng = deg2rad((float) $this->odp_lng - $lng);
        $a = sin($dLat/2) ** 2 + cos(deg2rad($lat)) * cos(deg2rad((float) $this->odp_lat)) * sin($dLng/2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
