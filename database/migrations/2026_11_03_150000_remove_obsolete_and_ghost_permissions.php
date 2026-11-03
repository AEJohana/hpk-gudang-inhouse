<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membersihkan 6 izin yang usang / ghost / redundan:
     * 1. work-request.monitoring (SLA sudah menjadi banner terbuka & filter cepat di WRI)
     * 2. work-request.view-all (Ghost - sistem menggunakan kolom users.can_access_all_departments)
     * 3. audit-log.export (Fitur ekspor tidak pernah ada di aplikasi)
     * 4. admin-panel.view (Redundan terhadap proteksi role:admin_gudang)
     * 5. admin-panel.users (Redundan terhadap proteksi role:admin_gudang)
     * 6. admin-panel.settings (Redundan terhadap proteksi role:admin_gudang)
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $obsoletePermissions = [
            'work-request.monitoring',
            'work-request.view-all',
            'audit-log.export',
            'admin-panel.view',
            'admin-panel.users',
            'admin-panel.settings',
        ];

        foreach ($obsoletePermissions as $permName) {
            $perm = Permission::where('name', $permName)->first();
            if ($perm) {
                DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
                DB::table('model_has_permissions')->where('permission_id', $perm->id)->delete();
                $perm->delete();
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $obsoletePermissions = [
            'work-request.monitoring',
            'work-request.view-all',
            'audit-log.export',
            'admin-panel.view',
            'admin-panel.users',
            'admin-panel.settings',
        ];

        foreach ($obsoletePermissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
