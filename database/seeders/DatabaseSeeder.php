<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
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

        // Default admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@ikr.local'],
            [
                'name'     => 'Admin IKR',
                'password' => Hash::make('password'),
                'phone'    => '081234567890',
            ]
        );
        $admin->assignRole('admin');

        // Demo users (password: 'password')
        $demo = [
            ['name' => 'Demo Sales',       'email' => 'sales@ikr.local',       'role' => 'sales'],
            ['name' => 'Demo SalesLeader', 'email' => 'salesleader@ikr.local', 'role' => 'sales_leader'],
            ['name' => 'Demo LeaderTeknisi','email' => 'leadteknisi@ikr.local','role' => 'leader_teknisi'],
            ['name' => 'Demo Teknisi 1',   'email' => 'teknisi1@ikr.local',    'role' => 'teknisi'],
            ['name' => 'Demo Teknisi 2',   'email' => 'teknisi2@ikr.local',    'role' => 'teknisi'],
        ];
        foreach ($demo as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                ['name' => $u['name'], 'password' => Hash::make('password'), 'phone' => '081234567891']
            );
            $user->assignRole($u['role']);
        }
    }
}
