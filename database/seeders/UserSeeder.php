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
        $deptGudang = \App\Models\Department::where('code', '11')->first();
        $deptM1 = \App\Models\Department::where('code', '2')->first();

        $users = [
            [
                'email' => 'admin@hydraxle.com',
                'name' => 'Budi Santoso',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin_gudang',
                'work_area' => 'semua',
                'department_id' => $deptGudang?->id,
                'can_access_all_departments' => true,
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
                'work_area' => 'semua',
                'department_id' => $deptGudang?->id,
                'can_access_all_departments' => true,
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
                'work_area' => 'gudang',
                'department_id' => $deptGudang?->id,
                'can_access_all_departments' => false,
                'department' => 'Gudang Lapangan',
                'phone' => '0812-3456-7892',
                'is_active' => true,
            ],
            [
                'email' => 'engineer@hydraxle.com',
                'name' => 'Dedi Pratama',
                'username' => 'engineer',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'work_area' => 'mesin_center',
                'department_id' => $deptM1?->id ?? $deptGudang?->id,
                'can_access_all_departments' => false,
                'department' => 'Engineering Karoseri',
                'phone' => '0812-3456-7893',
                'is_active' => true,
            ],
            [
                'email' => 'qc@hydraxle.com',
                'name' => 'Hendra Kusuma',
                'username' => 'qc',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'work_area' => 'gudang',
                'department_id' => $deptGudang?->id,
                'can_access_all_departments' => false,
                'department' => 'Quality Control',
                'phone' => '0812-3456-7894',
                'is_active' => true,
            ],
            [
                'email' => 'aryajohana190@gmail.com',
                'name' => 'Eka Arya Johana',
                'username' => 'eka.johana',
                'password' => Hash::make('Adr12345'),
                'role' => 'admin_gudang',
                'work_area' => 'semua',
                'department_id' => $deptGudang?->id,
                'can_access_all_departments' => true,
                'department' => 'Administrator WMS',
                'phone' => '0812-9988-7766',
                'is_active' => true,
            ],
            [
                'email' => 'rchmnalha@hydraxle.com',
                'name' => 'Ali Rachman Hakim',
                'username' => 'rchmnalha',
                'password' => Hash::make('18092004'),
                'role' => 'admin_gudang',
                'work_area' => 'semua',
                'department_id' => $deptGudang?->id,
                'can_access_all_departments' => true,
                'department' => 'Administrator WMS',
                'phone' => '0813-1122-3344',
                'is_active' => true,
            ],
            // Akun Operasional Lapangan Legacy
            [
                'email' => 'panji@hydraxle.com',
                'name' => 'Pak Panji',
                'username' => 'Panji',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'work_area' => 'gudang',
                'department_id' => $deptGudang?->id,
                'can_access_all_departments' => false,
                'department' => 'Dept Gudang',
                'phone' => '0812-1111-2222',
                'is_active' => true,
            ],
            [
                'email' => 'denis@hydraxle.com',
                'name' => 'Denis',
                'username' => 'Denis',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'work_area' => 'gudang',
                'department_id' => $deptGudang?->id,
                'can_access_all_departments' => false,
                'department' => 'Dept Gudang',
                'phone' => '0812-3333-4444',
                'is_active' => true,
            ],
            [
                'email' => 'nurdin@hydraxle.com',
                'name' => 'Pak Nurdin',
                'username' => 'Nurdin',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'work_area' => 'gudang',
                'department_id' => $deptGudang?->id,
                'can_access_all_departments' => false,
                'department' => 'Dept Gudang',
                'phone' => '0812-5555-6666',
                'is_active' => true,
            ],
            [
                'email' => 'fajar@hydraxle.com',
                'name' => 'Pak Fajar',
                'username' => 'Fajar_M1',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'work_area' => 'mesin_center',
                'department_id' => $deptM1?->id,
                'can_access_all_departments' => false,
                'department' => 'Dept M1',
                'phone' => '0812-7777-8888',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['username' => $userData['username']],
                $userData
            );

            // Assign Spatie Role if role exists
            if ($user->role && Role::where('name', $user->role)->where('guard_name', 'web')->exists()) {
                $user->syncRoles([$user->role]);
            }

            // Default sync: Bagian Gudang dan Global User berhak membuat WR
            if ($user->work_area === 'gudang' || $user->can_access_all_departments) {
                $user->givePermissionTo('work-request.create');
            }
        }
    }
}
