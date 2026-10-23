<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WorkRequest;
use App\Models\WorkRequestLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkRequestDefectAndHandoverTest extends TestCase
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

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Test Part');
            $table->string('part_number')->nullable();
            $table->string('uom')->default('PCS');
            $table->string('process_route')->nullable();
            $table->timestamps();
        });

        Schema::create('work_requests', function (Blueprint $table) {
            $table->id();
            $table->string('wri_number', 50)->unique();
            $table->unsignedBigInteger('component_id')->nullable();
            $table->unsignedBigInteger('requested_by_user_id')->nullable();
            $table->string('dept_pengaju', 100)->nullable();
            $table->string('dept_penerima', 100)->nullable();
            $table->string('routing_text', 255)->nullable();
            $table->string('approval_pengaju', 50)->default('Diterima');
            $table->string('approval_penerima', 50)->default('Diterima');
            $table->decimal('quantity_requested', 10, 2);
            $table->decimal('quantity_produced', 10, 2)->default(0);
            $table->decimal('quantity_received', 10, 2)->default(0);
            $table->decimal('quantity_defect', 10, 2)->default(0);
            $table->decimal('current_batch_qty', 10, 2)->default(0);
            $table->string('status', 100)->default('Dalam antrian');
            $table->string('current_process_status', 100)->default('Dalam antrian');
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });

        Schema::create('work_request_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_request_id');
            $table->integer('step_number');
            $table->unsignedBigInteger('machine_id')->nullable();
            $table->string('process_name', 100);
            $table->string('status', 50)->default('pending');
            $table->string('operator_name', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('work_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_request_id');
            $table->string('action_type', 50);
            $table->string('status_label', 150);
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('quantity_accumulated', 10, 2)->default(0);
            $table->string('operator_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function test_original_quantity_requested_calculation(): void
    {
        $wr = new WorkRequest([
            'quantity_requested' => 5,
            'quantity_defect' => 5,
        ]);

        $this->assertEquals(10.0, $wr->original_quantity_requested);
    }

    public function test_get_step_balance_deducts_machine_defects(): void
    {
        $wr = WorkRequest::create([
            'wri_number' => 'WRI-TEST-001',
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept Laser',
            'routing_text' => 'Laser',
            'quantity_requested' => 5, // 10 dikurangi 5 defect
            'quantity_defect' => 5,
            'status' => 'Proses: Laser (Out)',
        ]);

        // Simpan log pengerjaan: In = 10, Out = 5, Defect = 5
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Proses: Laser (In)',
            'quantity' => 10,
            'quantity_accumulated' => 10,
        ]);

        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'defect',
            'status_label' => 'Defect > Proses: Laser (Out)',
            'quantity' => 5,
            'quantity_accumulated' => 5,
            'notes' => '5 pcs cacat potong di Laser',
        ]);

        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Proses: Laser (Out)',
            'quantity' => 5,
            'quantity_accumulated' => 5,
        ]);

        $bal = $wr->getStepBalance('Laser', 0);

        // totalIn = 10, totalOut = 5, defect = 5 => saldoOut = 10 - 5 - 5 = 0
        $this->assertEquals(0, $bal['saldo_out'], 'Saldo out mesin harus 0 karena sisa pesanan sudah dipotong defect');
        $this->assertEquals(5, $bal['total_out'], 'Total unit bagus keluar mesin adalah 5');
        $this->assertEquals(5, $bal['machine_defects'], 'Total defect mesin tercatat 5');
    }

    public function test_mh_pickup_balance_deducts_defects_and_missing_items(): void
    {
        $wr = WorkRequest::create([
            'wri_number' => 'WRI-TEST-002',
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept Laser',
            'routing_text' => 'Laser -> Bending',
            'quantity_requested' => 19, // 20 dikurangi 1 defect
            'quantity_defect' => 1,
            'status' => 'Proses: Laser (Out)',
        ]);

        // Log: Out dari Laser = 20
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Proses: Laser (Out)',
            'quantity' => 20,
            'quantity_accumulated' => 20,
        ]);

        // MH mengambil: 19 pcs bagus, 1 pcs defect/hilang
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'defect',
            'status_label' => 'Defect > MH Pickup: Laser',
            'quantity' => 1,
            'quantity_accumulated' => 1,
            'notes' => '1 pcs hilang saat penjemputan MH',
        ]);

        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'MH Pickup: Laser',
            'quantity' => 19,
            'quantity_accumulated' => 19,
        ]);

        $bal = $wr->getStepBalance('Laser', 0);

        // waiting_pickup = totalOut (20) - totalPickup (19) - pickupDefects (1) = 0
        $this->assertEquals(0, $bal['waiting_pickup'], 'Sisa antrian penjemputan MH harus 0 setelah defect dan unit bagus diambil');
    }

    public function test_gudang_receiving_action_deducts_defects(): void
    {
        $userGudang = new User([
            'name' => 'Staf Gudang',
            'work_area' => 'gudang',
            'department' => 'Dept Gudang',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WRI-TEST-003',
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept Laser',
            'routing_text' => 'Laser',
            'quantity_requested' => 19,
            'quantity_defect' => 1,
            'status' => 'Menuju Pengaju',
        ]);

        // Menuju Pengaju = 20
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Menuju Pengaju',
            'quantity' => 20,
            'quantity_accumulated' => 20,
        ]);

        // Gudang menerima 19, 1 defect transit
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'defect',
            'status_label' => 'Defect > Sudah Diambil',
            'quantity' => 1,
            'quantity_accumulated' => 1,
            'notes' => '1 pcs rusak saat diantar troli MH',
        ]);

        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Sudah Diambil',
            'quantity' => 19,
            'quantity_accumulated' => 19,
        ]);

        $actions = $wr->getAvailableActionsForUser($userGudang);

        // Karena 19 unit sudah diterima dan 1 unit defect (total 20 unit terhitung), saldoTerima = 20 - 19 - 1 = 0
        $this->assertEmpty($actions, 'Tidak boleh ada sisa aksi penerimaan gudang karena seluruh unit telah tuntas diproses');
    }
}
