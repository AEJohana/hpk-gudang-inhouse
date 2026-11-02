<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. The 34 Tailored Permissions
        $tailoredPermissions = [
            // Work Request Inhouse (8)
            'work-request.view',
            'work-request.create',
            'work-request.process',
            'work-request.approve',
            'work-request.print',
            'work-request.monitoring',
            'work-request.safety-stock',
            'work-request.view-all',

            // Material Handling (3)
            'material-handling.view',
            'material-handling.manage',
            'material-handling.process',

            // Peta Gudang (3)
            'peta-gudang.view',
            'peta-gudang.create',
            'peta-gudang.edit',

            // Master Komponen (4)
            'master-komponen.view',
            'master-komponen.create',
            'master-komponen.edit',
            'master-komponen.delete',

            // Transaksi Komponen (2)
            'transaksi-komponen.view',
            'transaksi-komponen.create',

            // Label QR (3)
            'label-qr.view',
            'label-qr.create',
            'label-qr.print',

            // ECR (3)
            'ecr.view',
            'ecr.create',
            'ecr.approve',

            // Disposal (3)
            'disposal.view',
            'disposal.create',
            'disposal.approve',

            // Cycle Count (3)
            'cycle-count.view',
            'cycle-count.create',
            'cycle-count.approve',

            // Log Aktivitas & Audit (2)
            'audit-log.view',
            'audit-log.export',

            // Admin Panel (3)
            'admin-panel.view',
            'admin-panel.users',
            'admin-panel.settings',
        ];

        foreach ($tailoredPermissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 2. Re-sync default roles with tailored permissions
        $roles = Role::all();
        foreach ($roles as $role) {
            if ($role->name === 'admin_gudang') {
                $role->syncPermissions($tailoredPermissions);
            } elseif ($role->name === 'supervisor') {
                $role->syncPermissions([
                    'peta-gudang.view',
                    'master-komponen.view',
                    'transaksi-komponen.view',
                    'ecr.view', 'ecr.approve',
                    'disposal.view', 'disposal.approve',
                    'label-qr.view', 'label-qr.print',
                    'cycle-count.view', 'cycle-count.approve',
                    'audit-log.view', 'audit-log.export',
                    'work-request.view',
                    'work-request.create',
                    'work-request.process',
                    'work-request.approve',
                    'work-request.print',
                    'work-request.monitoring',
                    'work-request.safety-stock',
                    'work-request.view-all',
                    'material-handling.view',
                    'material-handling.manage',
                    'material-handling.process',
                ]);
            } elseif ($role->name === 'operator_mesin') {
                $role->syncPermissions([
                    'peta-gudang.view',
                    'master-komponen.view',
                    'label-qr.view',
                    'work-request.view',
                    'work-request.process',
                    'work-request.print',
                ]);
            } elseif ($role->name === 'operator_mh') {
                $role->syncPermissions([
                    'peta-gudang.view',
                    'label-qr.view',
                    'label-qr.print',
                    'work-request.view',
                    'material-handling.view',
                    'material-handling.manage',
                    'material-handling.process',
                ]);
            } elseif ($role->name === 'qc') {
                $role->syncPermissions([
                    'master-komponen.view',
                    'ecr.view', 'ecr.approve',
                    'disposal.view', 'disposal.approve',
                    'work-request.view',
                ]);
            } elseif ($role->name === 'engineering') {
                $role->syncPermissions([
                    'master-komponen.view', 'master-komponen.create', 'master-komponen.edit',
                    'ecr.view', 'ecr.create',
                    'work-request.view',
                    'work-request.monitoring',
                ]);
            }
        }

        // 3. Remove obsolete ghost permissions safely
        Permission::whereNotIn('name', $tailoredPermissions)->delete();

        // Flush Spatie cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Flush Spatie cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
