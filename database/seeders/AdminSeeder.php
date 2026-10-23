<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Generic Standard Module Permissions
        $modules = ['Peta Gudang', 'Master Komponen', 'Transaksi Komponen', 'ECR', 'Disposal', 'Label QR', 'Cycle Count', 'Laporan', 'Admin Panel'];
        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'export'];

        foreach ($modules as $module) {
            $slug = Str::slug($module);
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$slug}.{$action}"]);
            }
        }

        // 2. Dedicated Work Request Inhouse (WRI) Granular Permissions
        $wriPermissions = [
            'work-request.view',        // Akses Utama Menu & Lihat Daftar WRI
            'work-request.create',      // Sub-Akses: Buat Tiket WRI Baru
            'work-request.process',     // Sub-Akses: Eksekusi Pengerjaan Mesin, Advance Step & Lapor Defect
            'work-request.approve',     // Sub-Akses: Otorisasi Approval Ka. Bagian Pengaju & Penerima
            'work-request.print',       // Sub-Akses: Cetak Lembar WRI, Kanban & Label Barcode
            'work-request.monitoring',  // Sub-Akses: Dashboard Monitoring SLA & Analitik Durasi Mesin
            'work-request.safety-stock',// Sub-Akses: Tab Pemantauan Part Kritis & Order Cepat
            'work-request.view-all',    // Sub-Akses Khusus: Akses Global Tiket Semua 15 Departemen (*)
        ];

        foreach ($wriPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // 3. Dedicated Material Handling (MH) Permissions
        $mhPermissions = [
            'material-handling.view',   // Lihat Dashboard & Logistik MH
            'material-handling.manage', // Eksekusi MH Pickup, Transit, Drop & Scan
            'material-handling.process', // Sub-Akses: Eksekusi Serah-Terima MH
        ];

        foreach ($mhPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // 4. Setup Roles & Sync Permissions
        // Role: Admin Gudang (Super User)
        $roleAdmin = Role::firstOrCreate(['name' => 'admin_gudang', 'guard_name' => 'web']);
        $roleAdmin->syncPermissions(Permission::all());

        // Role: Supervisor / Ka. Bagian (Pengaju & Manajerial)
        $roleSupervisor = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $roleSupervisor->syncPermissions([
            'peta-gudang.view',
            'master-komponen.view', 'master-komponen.export',
            'transaksi-komponen.view', 'transaksi-komponen.export',
            'ecr.view', 'ecr.approve',
            'disposal.view', 'disposal.approve',
            'label-qr.view',
            'cycle-count.view', 'cycle-count.approve',
            'laporan.view', 'laporan.export',
            // WRI & MH
            'work-request.view',
            'work-request.create',
            'work-request.process',
            'work-request.approve',
            'work-request.print',
            'work-request.monitoring',
            'work-request.safety-stock',
            'material-handling.view',
            'material-handling.manage',
        ]);

        // Role: Operator (Hak akses spesifik diberikan per-user via checklist matrix berdasarkan area kerja)
        $roleOperator = Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
        $roleOperator->syncPermissions([]);

        // Role Baru: Operator Mesin Fabrikasi (Penerima: Laser, SB, M1, UNP, Robot)
        $roleOperatorMesin = Role::firstOrCreate(['name' => 'operator_mesin', 'guard_name' => 'web']);
        $roleOperatorMesin->syncPermissions([
            'peta-gudang.view',
            'master-komponen.view',
            'label-qr.view',
            // WRI (Hanya melihat tiket mesinnya, proses pengerjaan, dan cetak lembar kerja)
            'work-request.view',
            'work-request.process',
            'work-request.print',
        ]);

        // Role Baru: Operator Material Handling (MH Lapangan)
        $roleOperatorMH = Role::firstOrCreate(['name' => 'operator_mh', 'guard_name' => 'web']);
        $roleOperatorMH->syncPermissions([
            'peta-gudang.view',
            'label-qr.view',
            'work-request.view',
            'material-handling.view',
            'material-handling.manage',
        ]);

        // Role: Quality Control (QC)
        $roleQC = Role::firstOrCreate(['name' => 'qc', 'guard_name' => 'web']);
        $roleQC->syncPermissions([
            'master-komponen.view',
            'ecr.view', 'ecr.approve',
            'disposal.view', 'disposal.approve',
            'work-request.view',
        ]);

        // Role: Engineering Karoseri
        $roleEngineering = Role::firstOrCreate(['name' => 'engineering', 'guard_name' => 'web']);
        $roleEngineering->syncPermissions([
            'master-komponen.view',
            'ecr.view', 'ecr.create', 'ecr.edit',
            'work-request.view',
            'work-request.monitoring',
        ]);

        // Role: Staff (Hak akses spesifik diberikan per-user via checklist matrix berdasarkan area kerja)
        $roleStaff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $roleStaff->syncPermissions([]);

        // 5. Hubungkan Pengguna Awal ke Master Departemen & Sync Role
        $deptGudang = Department::where('code', '11')->first();
        $deptM1 = Department::where('code', '2')->first();
        $deptLaser = Department::where('code', '1')->first();
        $deptUji1 = Department::where('code', '14')->first();

        $users = User::all();
        foreach ($users as $user) {
            // Assign department_id and work_area
            if (!$user->work_area) {
                if ($user->hasRole('operator_mesin') || $user->hasRole('engineering')) {
                    $user->work_area = 'mesin_center';
                } elseif ($user->hasRole('operator_mh')) {
                    $user->work_area = 'material_handling';
                } else {
                    $user->work_area = 'gudang';
                }
            }

            if (!$user->department_id) {
                if ($user->hasRole('admin_gudang') || $user->username === 'admin') {
                    $user->department_id = $deptGudang?->id;
                    $user->can_access_all_departments = true; // Admin has global access (*)
                } elseif ($user->hasRole('supervisor') || $user->username === 'spv_gudang') {
                    $user->department_id = $deptGudang?->id;
                } elseif ($user->hasRole('operator') || $user->username === 'operator') {
                    $user->department_id = $deptGudang?->id;
                } elseif ($user->hasRole('engineering')) {
                    $user->department_id = $deptM1?->id ?? $deptGudang?->id;
                } elseif ($user->hasRole('qc')) {
                    $user->department_id = $deptUji1?->id ?? $deptGudang?->id;
                }
            }
            $user->save();

            if ($user->role && Role::where('name', $user->role)->where('guard_name', 'web')->exists()) {
                $user->syncRoles([$user->role]);
            }
        }
    }
}
