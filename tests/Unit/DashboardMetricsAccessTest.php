<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardMetricsAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('alias')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->default('hashed');
            $table->string('role')->default('operator');
            $table->string('work_area')->default('mesin_center');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('department')->nullable();
            $table->boolean('can_access_all_departments')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function test_admin_can_view_warehouse_metrics(): void
    {
        $admin = new User([
            'name' => 'Admin Gudang',
            'role' => 'admin_gudang',
            'work_area' => 'gudang',
        ]);

        $this->assertTrue($admin->canViewWarehouseMetrics());

        $systemAdmin = new User([
            'name' => 'System Admin',
            'role' => 'admin',
            'work_area' => 'mesin_center',
        ]);

        $this->assertTrue($systemAdmin->canViewWarehouseMetrics());
    }

    public function test_supervisor_can_view_warehouse_metrics(): void
    {
        $spv = new User([
            'name' => 'Supervisor Lini',
            'role' => 'supervisor',
            'work_area' => 'mesin_center',
        ]);

        $this->assertTrue($spv->canViewWarehouseMetrics());
    }

    public function test_global_access_user_can_view_warehouse_metrics(): void
    {
        $globalUser = new User([
            'name' => 'General Manager',
            'role' => 'staff',
            'work_area' => 'mesin_center',
            'can_access_all_departments' => true,
        ]);

        $this->assertTrue($globalUser->canViewWarehouseMetrics());
    }

    public function test_gudang_personnel_can_view_warehouse_metrics(): void
    {
        // 1. Staff Gudang with work_area gudang
        $staffGudang = new User([
            'name' => 'Staff Gudang 1',
            'role' => 'staff',
            'work_area' => 'gudang',
        ]);
        $this->assertTrue($staffGudang->canViewWarehouseMetrics());

        // 2. Operator Gudang with work_area gudang
        $operatorGudang = new User([
            'name' => 'Operator Gudang 1',
            'role' => 'operator',
            'work_area' => 'gudang',
        ]);
        $this->assertTrue($operatorGudang->canViewWarehouseMetrics());

        // 3. User with department name 'Gudang'
        $deptGudangUser = new User([
            'name' => 'Petugas Rak Gudang',
            'role' => 'operator',
            'work_area' => 'mesin_center',
            'department' => 'Dept Gudang Inhouse',
        ]);
        $this->assertTrue($deptGudangUser->canViewWarehouseMetrics());
    }

    public function test_non_gudang_staff_and_operators_cannot_view_warehouse_metrics(): void
    {
        // 1. Operator Mesin Laser
        $operatorLaser = new User([
            'name' => 'Operator Laser Cutting',
            'role' => 'operator',
            'work_area' => 'mesin_center',
            'department' => 'Dept Laser',
        ]);
        $this->assertFalse($operatorLaser->canViewWarehouseMetrics());

        // 2. Operator Mesin Band Saw
        $operatorBandSaw = new User([
            'name' => 'Operator Band Saw',
            'role' => 'operator',
            'work_area' => 'mesin_center',
            'department' => 'Dept Band Saw',
        ]);
        $this->assertFalse($operatorBandSaw->canViewWarehouseMetrics());

        // 3. Staff Mesin M1
        $staffM1 = new User([
            'name' => 'Staff M1 Mesin Center',
            'role' => 'staff',
            'work_area' => 'mesin_center',
            'department' => 'Dept M1',
        ]);
        $this->assertFalse($staffM1->canViewWarehouseMetrics());

        // 4. Operator Material Handling outside Gudang
        $operatorMh = new User([
            'name' => 'Operator MH Jalur',
            'role' => 'operator',
            'work_area' => 'material_handling',
            'department' => 'Dept Fabrikasi',
        ]);
        $this->assertFalse($operatorMh->canViewWarehouseMetrics());
    }
}
