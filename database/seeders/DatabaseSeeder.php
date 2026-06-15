<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $roles = ['sales', 'sales_leader', 'leader_teknisi', 'teknisi', 'admin'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // Default marker — bcrypt dari string literal "CHANGE_ME_"
        // supaya GitGuardian tidak flag dan operator WAJIB ganti.
        // Lihat SECURITY.md.
        $defaultPlain = 'CHANGE_ME_' . Str::random(8);
        $defaultHash  = Hash::make($defaultPlain);

        // Cetak plaintext password HANYA di console saat seeding
        $this->command->warn('=== DEFAULT DEMO PASSWORDS (COPY NOW, UBAH setelah first login) ===');
        $this->command->warn("admin@ikr.local       : {$defaultPlain}");
        $this->command->warn("sales@ikr.local       : {$defaultPlain}");
        $this->command->warn("salesleader@ikr.local : {$defaultPlain}");
        $this->command->warn("leadteknisi@ikr.local : {$defaultPlain}");
        $this->command->warn("teknisi1@ikr.local    : {$defaultPlain}");
        $this->command->warn("teknisi2@ikr.local    : {$defaultPlain}");
        $this->command->warn('=====================================================================');

        $defaults = [
            ['name' => 'Admin IKR',         'email' => 'admin@ikr.local',       'phone' => '081234567890', 'role' => 'admin'],
            ['name' => 'Demo Sales',        'email' => 'sales@ikr.local',       'phone' => '081234567891', 'role' => 'sales'],
            ['name' => 'Demo SalesLeader',  'email' => 'salesleader@ikr.local', 'phone' => '081234567892', 'role' => 'sales_leader'],
            ['name' => 'Demo LeaderTeknisi','email' => 'leadteknisi@ikr.local', 'phone' => '081234567893', 'role' => 'leader_teknisi'],
            ['name' => 'Demo Teknisi 1',    'email' => 'teknisi1@ikr.local',    'phone' => '081234567894', 'role' => 'teknisi'],
            ['name' => 'Demo Teknisi 2',    'email' => 'teknisi2@ikr.local',    'phone' => '081234567895', 'role' => 'teknisi'],
        ];

        foreach ($defaults as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'name'              => $d['name'],
                    'password'          => $defaultHash,
                    'phone'             => $d['phone'],
                    'email_verified_at' => now(), // bug #1 fix: required for 'verified' middleware
                ]
            );
            $user->assignRole($d['role']);
        }
    }
}
