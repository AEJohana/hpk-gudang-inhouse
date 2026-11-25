<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Component;
use App\Models\WorkRequest;
use App\Models\WorkRequestStep;
use App\Models\WorkRequestLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WorkRequestPartialLifecycleTest extends TestCase
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

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('work_area')->nullable();
            $table->string('department')->nullable();
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
            $table->string('assigned_operator_name', 100)->nullable();
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

    public function test_partial_delivery_preserves_machine_process_status_until_finished(): void
    {
        $component = Component::create([
            'name' => 'Shaft M1',
            'part_number' => 'SH-M1-001',
            'uom' => 'PCS',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WRI/20261125/0001',
            'component_id' => $component->id,
            'quantity_requested' => 10,
            'quantity_produced' => 0,
            'quantity_received' => 0,
            'status' => 'submitted',
            'current_process_status' => 'submitted',
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'assigned_operator_name' => 'Dedi Pratama',
            'is_final' => false,
        ]);

        WorkRequestStep::create([
            'work_request_id' => $wr->id,
            'step_number' => 1,
            'process_name' => 'Dept M1',
            'status' => 'pending',
        ]);

        $operatorM1 = User::create([
            'name' => 'Dedi Pratama',
            'username' => 'dedi_m1',
            'email' => 'dedi@hpk.local',
            'password' => bcrypt('password'),
            'work_area' => 'mesin_center',
        ]);

        $gudangUser = User::create([
            'name' => 'Pak Panji',
            'username' => 'panji_gudang',
            'email' => 'panji@hpk.local',
            'password' => bcrypt('password'),
            'work_area' => 'gudang',
        ]);

        // FASE 1: Mulai pengerjaan 10 pcs (In)
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Proses: Dept M1 (In)',
            'quantity' => 10,
            'quantity_accumulated' => 10,
            'operator_name' => 'Dedi Pratama',
        ]);
        $wr->update(['status' => 'Proses: Dept M1 (In)']);

        $bal = $wr->getStepBalance('Dept M1', 0);
        $this->assertEquals(10, $bal['total_in']);
        $this->assertEquals(0, $bal['total_out']);
        $this->assertEquals(10, $bal['saldo_out']);
        $this->assertEquals(0, $bal['waiting_pickup']);
        $this->assertFalse($wr->hasPhysicalWaitingPickup());
        $this->assertEquals(0, $wr->getInTransitQuantity());
        $this->assertEquals('Proses: Dept M1 (Out)', $wr->getNextStatusLabel(null));

        // FASE 2: Mesin menyelesaikan cicilan 5 pcs (Out -> Siap Diambil)
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'partial',
            'status_label' => 'Parsial > Siap Diambil',
            'quantity' => 5,
            'quantity_accumulated' => 5,
            'operator_name' => 'Dedi Pratama',
        ]);
        $wr->update(['status' => 'Parsial > Siap Diambil']);

        $bal2 = $wr->getStepBalance('Dept M1', 0);
        $this->assertEquals(5, $bal2['total_out']);
        $this->assertEquals(5, $bal2['saldo_out']);
        $this->assertEquals(5, $bal2['waiting_pickup']);
        $this->assertTrue($wr->hasPhysicalWaitingPickup(), 'Harus bernilai true karena 5 pcs sudah selesai di mesin dan menunggu kurir MH');

        // FASE 3: Kurir MH menjemput 5 pcs (Menuju Pengaju)
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'partial',
            'status_label' => 'Parsial > Menuju Pengaju',
            'quantity' => 5,
            'quantity_accumulated' => 5,
            'operator_name' => 'Kurir MH',
        ]);
        $wr->update(['status' => 'Parsial > Menuju Pengaju']);

        $bal3 = $wr->getStepBalance('Dept M1', 0);
        $this->assertEquals(5, $bal3['total_pickup']);
        $this->assertEquals(0, $bal3['waiting_pickup']);
        $this->assertFalse($wr->hasPhysicalWaitingPickup(), 'Tidak ada barang menunggu jemputan karena sudah dibawa kurir');
        $this->assertEquals(5, $wr->getInTransitQuantity(), '5 pcs sedang dalam perjalanan kurir MH');

        // FASE 4: Gudang menerima 5 pcs (Sudah Diambil)
        // INILAH KASUS ISU: Gudang telah menerima 5 pcs, sisa 5 pcs masih di M1
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'partial',
            'status_label' => 'Parsial > Sudah Diambil',
            'quantity' => 5,
            'quantity_accumulated' => 5,
            'operator_name' => 'Pak Panji',
        ]);
        $wr->update([
            'status' => 'Parsial > Sudah Diambil',
            'quantity_received' => 5,
            'is_final' => false,
        ]);

        $bal4 = $wr->getStepBalance('Dept M1', 0);
        $this->assertEquals(5, $bal4['total_out']);
        $this->assertEquals(5, $bal4['total_pickup']);
        $this->assertEquals(0, $bal4['waiting_pickup'], 'Tidak ada saldo menunggu pickup di mesin');
        $this->assertEquals(5, $bal4['saldo_out'], 'Sisa 5 pcs masih harus diselesaikan mesin M1');
        $this->assertFalse($wr->hasPhysicalWaitingPickup(), 'WAJIB FALSE: Tidak boleh memicu lencana Menunggu Diambil MH');
        $this->assertEquals(0, $wr->getInTransitQuantity(), 'WAJIB 0: Kurir sudah menyerahkan barang');

        // Tampilan status publik/gudang WAJIB mengarah ke pengerjaan sisa mesin (Bukan Siap Diambil)
        $this->assertEquals('Proses: Dept M1 (Out)', $wr->getNextStatusLabel(null));
        $this->assertEquals('Proses: Dept M1 (Out)', $wr->getNextStatusLabel($gudangUser));

        // Tombol operator mesin M1 tetap aktif untuk sisa 5 pcs
        $actionsM1 = $wr->getAvailableActionsForUser($operatorM1);
        $this->assertNotEmpty($actionsM1);
        $this->assertEquals('Siap Diambil', $actionsM1[0]['target_status']);
        $this->assertEquals(5, $actionsM1[0]['quantity']);

        // FASE 5: Mesin M1 menyelesaikan sisa 5 pcs
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Siap Diambil',
            'quantity' => 5,
            'quantity_accumulated' => 10,
            'operator_name' => 'Dedi Pratama',
        ]);
        $wr->update(['status' => 'Siap Diambil']);

        $bal5 = $wr->getStepBalance('Dept M1', 0);
        $this->assertEquals(10, $bal5['total_out']);
        $this->assertEquals(0, $bal5['saldo_out']);
        $this->assertEquals(5, $bal5['waiting_pickup'], 'Sisa 5 pcs sekarang siap dijemput MH');
        $this->assertTrue($wr->hasPhysicalWaitingPickup(), 'BARU SEKARANG bernilai TRUE untuk memicu Menunggu Diambil MH');

        // FASE 6 & 7: MH jemput dan Gudang terima tuntas 100%
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Menuju Pengaju',
            'quantity' => 5,
            'quantity_accumulated' => 10,
            'operator_name' => 'Kurir MH',
        ]);
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Sudah Diambil',
            'quantity' => 5,
            'quantity_accumulated' => 10,
            'operator_name' => 'Pak Panji',
        ]);
        $wr->update([
            'status' => 'Sudah Diambil',
            'quantity_received' => 10,
            'is_final' => true,
        ]);

        $this->assertTrue($wr->is_final);
        $this->assertFalse($wr->hasPhysicalWaitingPickup());
        $this->assertEquals(0, $wr->getInTransitQuantity());
    }
}
