<?php

namespace Tests\Unit;

use Tests\TestCase;

class PermissionMatrixIntegrityTest extends TestCase
{
    /**
     * The official 31 tailored permissions.
     */
    protected array $tailoredPermissions = [
        // Work Request Inhouse (6)
        'work-request.view',
        'work-request.create',
        'work-request.process',
        'work-request.approve',
        'work-request.print',
        'work-request.safety-stock',

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

        // Log Aktivitas & Audit (1)
        'audit-log.view',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Schema::dropIfExists('role_has_permissions');
        \Illuminate\Support\Facades\Schema::dropIfExists('model_has_permissions');
        \Illuminate\Support\Facades\Schema::dropIfExists('model_has_roles');
        \Illuminate\Support\Facades\Schema::dropIfExists('roles');
        \Illuminate\Support\Facades\Schema::dropIfExists('permissions');

        \Illuminate\Support\Facades\Schema::create('roles', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('permissions', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('model_has_roles', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        \Illuminate\Support\Facades\Schema::create('model_has_permissions', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        \Illuminate\Support\Facades\Schema::create('role_has_permissions', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->tailoredPermissions as $p) {
            \Spatie\Permission\Models\Permission::create(['name' => $p, 'guard_name' => 'web']);
        }
    }

    public function test_tailored_permissions_count_is_exactly_31(): void
    {
        $this->assertCount(31, $this->tailoredPermissions);
    }

    public function test_no_ghost_permissions_in_tailored_list(): void
    {
        $ghosts = [
            'work-request.monitoring',
            'work-request.view-all',
            'audit-log.export',
            'admin-panel.view',
            'admin-panel.users',
            'admin-panel.settings',
            'peta-gudang.approve',
            'peta-gudang.export',
            'peta-gudang.delete',
            'transaksi-komponen.edit',
            'transaksi-komponen.delete',
            'transaksi-komponen.approve',
            'transaksi-komponen.export',
            'label-qr.edit',
            'label-qr.delete',
            'label-qr.approve',
            'label-qr.export',
            'ecr.edit',
            'ecr.delete',
            'ecr.export',
            'disposal.edit',
            'disposal.delete',
            'disposal.export',
            'cycle-count.edit',
            'cycle-count.delete',
            'cycle-count.export',
            'laporan.view',
            'laporan.create',
            'laporan.edit',
            'laporan.delete',
            'laporan.approve',
            'laporan.export',
        ];

        foreach ($ghosts as $ghost) {
            $this->assertNotContains($ghost, $this->tailoredPermissions, "Ghost permission {$ghost} should not exist");
        }
    }

    public function test_user_controller_presets_contain_only_tailored_permissions(): void
    {
        $controller = new class extends \App\Http\Controllers\Admin\UserController {
            public function exposePresets(): array
            {
                return $this->getPermissionPresets();
            }
        };

        // Inject simulated all permissions
        $presets = $controller->exposePresets();

        $this->assertArrayHasKey('all', $presets);
        $this->assertArrayHasKey('supervisor', $presets);
        $this->assertArrayHasKey('admin_gudang', $presets);
        $this->assertArrayHasKey('staff', $presets);
        $this->assertArrayHasKey('operator', $presets);

        // Verify staff gudang presets
        $staffGudang = $presets['staff']['gudang'];
        $this->assertContains('master-komponen.view', $staffGudang);
        $this->assertContains('label-qr.print', $staffGudang);
        $this->assertContains('audit-log.view', $staffGudang);
        $this->assertNotContains('laporan.view', $staffGudang);

        // Verify staff mesin center presets (wajib memiliki work-request.approve untuk approval penerima & penugasan operator)
        $staffMesin = $presets['staff']['mesin_center'];
        $this->assertContains('work-request.view', $staffMesin);
        $this->assertContains('work-request.approve', $staffMesin, "Staff mesin center wajib memiliki izin approval penerima");
        $this->assertContains('work-request.process', $staffMesin);
        $this->assertContains('work-request.print', $staffMesin);
        $this->assertContains('master-komponen.view', $staffMesin);

        // Verify operator mesin center presets (operator mesin fisik tidak memiliki hak approval manajerial)
        $opMesin = $presets['operator']['mesin_center'];
        $this->assertContains('work-request.view', $opMesin);
        $this->assertNotContains('work-request.approve', $opMesin, "Operator mesin fisik tidak memiliki hak approval manajerial");
        $this->assertContains('work-request.process', $opMesin);
        $this->assertContains('work-request.print', $opMesin);

        // Verify operator material handling presets
        $opMH = $presets['operator']['material_handling'];
        $this->assertContains('material-handling.view', $opMH);
        $this->assertContains('material-handling.manage', $opMH);
        $this->assertContains('material-handling.process', $opMH);
        $this->assertContains('label-qr.print', $opMH);
    }
}
