<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@hydraxle.com',
                'name' => 'Budi Santoso',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin_gudang',
                'department' => 'Gudang & Logistik',
                'phone' => '0812-3456-7890',
                'is_active' => true,
            ],
            [
                'email' => 'spv.gudang@hydraxle.com',
                'name' => 'Agus Wijaya',
                'username' => 'spv_gudang',
                'password' => Hash::make('password'),
                'role' => 'supervisor',
                'department' => 'Kepala Gudang',
                'phone' => '0812-3456-7891',
                'is_active' => true,
            ],
            [
                'email' => 'operator@hydraxle.com',
                'name' => 'Rahmat Hidayat',
                'username' => 'operator',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'department' => 'Gudang Lapangan',
                'phone' => '0812-3456-7892',
                'is_active' => true,
            ],
            [
                'email' => 'engineer@hydraxle.com',
                'name' => 'Dedi Pratama',
                'username' => 'engineer',
                'password' => Hash::make('password'),
                'role' => 'engineering',
                'department' => 'Engineering Karoseri',
                'phone' => '0812-3456-7893',
                'is_active' => true,
            ],
            [
                'email' => 'qc@hydraxle.com',
                'name' => 'Hendra Kusuma',
                'username' => 'qc',
                'password' => Hash::make('password'),
                'role' => 'qc',
                'department' => 'Quality Control',
                'phone' => '0812-3456-7894',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Assign Spatie Role if role exists
            if ($user->role && Role::where('name', $user->role)->where('guard_name', 'web')->exists()) {
                $user->syncRoles([$user->role]);
            }
        }
    }
}
