<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsbHiosoChecklist extends Model
{
    protected $table = 'psb_hioso_checklists';
    public $timestamps = true;

    protected $fillable = [
        'psb_order_id', 'item_key', 'item_label',
        'is_checked', 'notes', 'checked_by', 'checked_at', 'sort_order',
    ];

    protected $casts = [
        'is_checked' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function psbOrder(): BelongsTo
    {
        return $this->belongsTo(PsbOrder::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /** Default HiOS checklist items (created on first transition to provisioning) */
    public static function defaultItems(): array
    {
        return [
            ['item_key' => 'cable_connected',  'item_label' => 'Kabel fiber terhubung ke ODP & ONT', 'sort_order' => 1],
            ['item_key' => 'sn_registered',    'item_label' => 'SN ONT terdaftar di OLT HiOS',       'sort_order' => 2],
            ['item_key' => 'vlan_set',         'item_label' => 'VLAN internet di-set di ONT',        'sort_order' => 3],
            ['item_key' => 'pppoe_configured', 'item_label' => 'PPPoE username & password di-set',    'sort_order' => 4],
            ['item_key' => 'wan_connected',    'item_label' => 'WAN connected & IP dapat',           'sort_order' => 5],
            ['item_key' => 'wifi_configured',  'item_label' => 'WiFi SSID & password di-set',        'sort_order' => 6],
            ['item_key' => 'test_ping',        'item_label' => 'Test ping 8.8.8.8 dari ONT berhasil','sort_order' => 7],
            ['item_key' => 'speed_test',       'item_label' => 'Speed test sesuai paket',             'sort_order' => 8],
        ];
    }
}
