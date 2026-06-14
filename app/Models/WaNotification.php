<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaNotification extends Model
{
    protected $table = 'wa_notifications';
    public $timestamps = true;

    protected $fillable = [
        'notifiable_type', 'notifiable_id',
        'channel', 'recipient', 'message',
        'payload', 'status', 'error', 'sent_at', 'attempts',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }
}
