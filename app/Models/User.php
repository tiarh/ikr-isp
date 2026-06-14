<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * User model — disinkronkan dgn saleskit.users (id sama).
 * IKR-ISP TIDAK duplicate table users — pake view atau FK ke saleskit.users.
 *
 * Untuk simplicity: kita punya table users lokal yang isinya mirror dari saleskit
 * (sync via job tiap 1 jam). Bisa juga langsung query saleskit.users via connection().
 */
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $table = 'users';
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['admin', 'sales_leader', 'leader_teknisi']);
    }

    public function teknisiOpenTicketCount(): int
    {
        try {
            return \DB::connection('ebilling')
                ->table('support_tickets')
                ->where('teknisi_id', $this->id)
                ->whereNotIn('status', ['closed', 'resolved'])
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
