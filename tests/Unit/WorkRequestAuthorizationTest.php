<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WorkRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkRequestAuthorizationTest extends TestCase
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

        Schema::create('permissions', function (Blueprint $table) {
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

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });
    }

    public function test_mesin_center_cannot_execute_mh_pickup(): void
    {
        $userM1 = new User([
            'name' => 'Fajar M1',
            'work_area' => 'mesin_center',
            'department' => 'Dept M1',
        ]);

        $wr = new WorkRequest([
            'wri_number' => 'WRI-001',
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'status' => 'MH Pickup: M1',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => false,
        ]);

        // Mesin Center user must NOT process MH Pickup, Menuju Pengaju, or Sudah Diambil
        $this->assertFalse($wr->canUserProcessAction($userM1, 'MH Pickup: M1'));
        $this->assertFalse($wr->canUserProcessAction($userM1, 'Menuju Pengaju'));
        $this->assertFalse($wr->canUserProcessAction($userM1, 'Sudah Diambil'));
    }

    public function test_material_handling_cannot_execute_machine_process(): void
    {
        $userMH = new User([
            'name' => 'Denis MH',
            'work_area' => 'material_handling',
            'department' => 'Dept Gudang',
        ]);

        $wr = new WorkRequest([
            'wri_number' => 'WRI-002',
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'status' => 'Proses: M1 (In)',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => false,
        ]);

        // Material Handling user must NOT process Machine In or Out
        $this->assertFalse($wr->canUserProcessAction($userMH, 'Proses: M1 (In)'));
        $this->assertFalse($wr->canUserProcessAction($userMH, 'Proses: M1 (Out)'));
    }

    public function test_mesin_center_can_execute_machine_process(): void
    {
        $userM1 = new User([
            'name' => 'Fajar M1',
            'work_area' => 'mesin_center',
            'department' => 'Dept M1',
        ]);

        $wr = new WorkRequest([
            'wri_number' => 'WRI-003',
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'status' => 'Proses: M1 (In)',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => false,
        ]);

        // Mesin Center user CAN process their own machine
        $this->assertTrue($wr->canUserProcessAction($userM1, 'Proses: M1 (In)'));
    }

    public function test_wri_view_excludes_material_handling_actions(): void
    {
        $userMH = new User([
            'name' => 'Kurir MH',
            'work_area' => 'material_handling',
            'department' => 'Dept Gudang',
        ]);

        $wr = new WorkRequest([
            'wri_number' => 'WRI-004',
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'status' => 'Siap Diambil',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => false,
        ]);

        // In WRI view ($forWriView = true), MH action is strictly prohibited
        $this->assertFalse($wr->canUserProcessAction($userMH, 'Menuju Pengaju', true));
        $this->assertFalse($wr->canUserProcessAction($userMH, 'MH Pickup: M1', true));

        // In MH module ($forWriView = false), authorized MH user CAN process
        $this->assertTrue($wr->canUserProcessAction($userMH, 'Menuju Pengaju', false));
        $this->assertTrue($wr->canUserProcessAction($userMH, 'MH Pickup: M1', false));
    }

    public function test_gudang_must_receive_goods_after_mh_delivery_and_ticket_remains_open(): void
    {
        $userGudang = new User([
            'name' => 'Staf Gudang',
            'work_area' => 'gudang',
            'department' => 'Dept Gudang',
        ]);
        $userMH = new User([
            'name' => 'Kurir MH',
            'work_area' => 'material_handling',
            'department' => 'Dept Gudang',
        ]);

        $wr = new WorkRequest([
            'wri_number' => 'WRI-005',
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'status' => 'Menuju Pengaju',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => false,
        ]);

        // When goods are in transit with MH ('Menuju Pengaju'):
        // 1. Ticket MUST remain open (not final)
        $this->assertFalse($wr->is_final);

        // 2. Gudang user CAN process final receive ('Sudah Diambil')
        $this->assertTrue($wr->canUserProcessAction($userGudang, 'Sudah Diambil'));

        // 3. MH courier CANNOT process warehouse receiving
        $this->assertFalse($wr->canUserProcessAction($userMH, 'Sudah Diambil'));
    }

    public function test_gudang_cannot_receive_goods_directly_from_machine_when_status_is_siap_diambil(): void
    {
        $userGudang = new User([
            'name' => 'Staf Gudang',
            'work_area' => 'gudang',
            'department' => 'Dept Gudang',
        ]);
        $userAdmin = new User([
            'name' => 'Admin Gudang',
            'work_area' => 'semua',
            'department' => 'Dept Gudang',
        ]);

        $wr = new WorkRequest([
            'wri_number' => 'WRI-006',
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'status' => 'Siap Diambil',
            'quantity_requested' => 100,
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => false,
        ]);

        // When goods are still sitting at the machine ('Siap Diambil') and not yet picked up by MH:
        // 1. Gudang user CANNOT receive goods directly
        $this->assertFalse($wr->canUserProcessAction($userGudang, 'Sudah Diambil'));

        // 2. Even Admin CANNOT receive goods directly without MH transit
        $this->assertFalse($wr->canUserProcessAction($userAdmin, 'Sudah Diambil'));

        // 3. Available actions for Gudang must NOT contain 'Sudah Diambil'
        $actions = $wr->getAvailableActionsForUser($userGudang);
        $hasReceiveAction = collect($actions)->contains('target_status', 'Sudah Diambil');
        $this->assertFalse($hasReceiveAction, 'Gudang dilarang memiliki aksi Scan Diterima sebelum barang dijemput MH');
    }
}
