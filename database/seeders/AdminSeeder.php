<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions based on matrix
        $modules = ['Peta Gudang', 'Master Komponen', 'Transaksi Komponen', 'ECR', 'Disposal', 'Label QR', 'Cycle Count', 'Laporan', 'Admin Panel'];
        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'export'];

        foreach ($modules as $module) {
            $slug = \Illuminate\Support\Str::slug($module);
            foreach ($actions as $action) {
                \Spatie\Permission\Models\Permission::firstOrCreate(['name' => "{$slug}.{$action}"]);
            }
        }

        // create roles and assign created permissions
        $roleAdmin = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin_gudang', 'guard_name' => 'web']);
        $roleAdmin->syncPermissions(\Spatie\Permission\Models\Permission::all());

        $roleSupervisor = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $roleSupervisor->syncPermissions([
            'peta-gudang.view',
            'master-komponen.view', 'master-komponen.export',
            'transaksi-komponen.view', 'transaksi-komponen.export',
            'ecr.view', 'ecr.approve',
            'disposal.view', 'disposal.approve',
            'label-qr.view',
            'cycle-count.view', 'cycle-count.approve',
            'laporan.view', 'laporan.export',
        ]);

        $roleOperator = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
        $roleOperator->syncPermissions([
            'peta-gudang.view',
            'master-komponen.view',
            'transaksi-komponen.view', 'transaksi-komponen.create',
            'label-qr.view', 'label-qr.create',
            'cycle-count.view', 'cycle-count.create',
        ]);

        $roleQC = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'qc', 'guard_name' => 'web']);
        $roleQC->syncPermissions([
            'master-komponen.view',
            'ecr.view', 'ecr.approve',
            'disposal.view', 'disposal.approve',
        ]);

        $roleEngineering = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'engineering', 'guard_name' => 'web']);
        $roleEngineering->syncPermissions([
            'master-komponen.view',
            'ecr.view', 'ecr.create', 'ecr.edit',
        ]);

        // Migrate existing users to use Spatie roles
        $users = \App\Models\User::all();
        foreach ($users as $user) {
            if ($user->role && \Spatie\Permission\Models\Role::where('name', $user->role)->where('guard_name', 'web')->exists()) {
                $user->syncRoles([$user->role]);
            }
        }
    }
}
