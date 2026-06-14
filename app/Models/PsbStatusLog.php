<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsbStatusLog extends Model
{
    protected $table = 'psb_status_logs';
    public $timestamps = true;

    protected $fillable = [
        'psb_order_id', 'from_status', 'to_status',
        'note', 'changed_by', 'meta',
    ];

    protected $casts = ['meta' => 'array'];

    public function psbOrder(): BelongsTo
    {
        return $this->belongsTo(PsbOrder::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
