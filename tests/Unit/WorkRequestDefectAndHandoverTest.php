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

    public function test_partial_delivery_keeps_ticket_open_and_allows_machine_to_finish_remaining_quantity(): void
    {
        $userM1 = new User([
            'name' => 'Operator Laser',
            'work_area' => 'mesin_center',
            'department' => 'Dept Laser',
        ]);

        $userMH = new User([
            'name' => 'Kurir Material Handling',
            'work_area' => 'material_handling',
            'department' => 'Dept Logistic',
        ]);

        $userGudang = new User([
            'name' => 'Staf Gudang',
            'work_area' => 'gudang',
            'department' => 'Dept Gudang',
        ]);

        // Buat WorkRequest: Pengajuan 100 pcs untuk Mesin Laser (M1)
        $wr = WorkRequest::create([
            'wri_number' => 'WRI-PARTIAL-100',
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept Laser',
            'routing_text' => 'Laser',
            'quantity_requested' => 100,
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'status' => 'Dalam antrian',
            'is_final' => false,
        ]);

        // 1. M1 mengambil bahan (In): 100 pcs
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Proses: Laser (In)',
            'quantity' => 100,
            'quantity_accumulated' => 100,
        ]);
        $wr->update(['status' => 'Proses: Laser (In)']);

        // 2. M1 memproses parsial (Out) batch 1: 50 pcs (sisa 50 pcs masih di mesin)
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'partial',
            'status_label' => 'Parsial > Siap Diambil',
            'quantity' => 50,
            'quantity_accumulated' => 50,
        ]);
        $wr->update(['status' => 'Parsial > Siap Diambil', 'current_batch_qty' => 50]);

        // 3. Material Handling pickup batch 1: 50 pcs
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'partial',
            'status_label' => 'Parsial > Menuju Pengaju',
            'quantity' => 50,
            'quantity_accumulated' => 50,
        ]);
        $wr->update(['status' => 'Parsial > Menuju Pengaju', 'current_batch_qty' => 50]);

        // 4. Gudang menerima batch 1: 50 pcs (Sudah Diambil)
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'partial',
            'status_label' => 'Parsial > Sudah Diambil',
            'quantity' => 50,
            'quantity_accumulated' => 50,
        ]);
        $wr->update([
            'status' => 'Parsial > Sudah Diambil',
            'quantity_received' => 50,
            'is_final' => false,
            'current_batch_qty' => 0,
        ]);

        // VERIFIKASI FASE PARSIAL:
        // A. Tiket TIDAK boleh final
        $this->assertFalse($wr->is_final, 'Tiket parsial tidak boleh berstatus is_final=true');

        // B. M1 HARUS tetap memiliki aksi pengerjaan untuk 50 pcs sisanya
        $actionsM1 = $wr->getAvailableActionsForUser($userM1);
        $this->assertNotEmpty($actionsM1, 'Operator M1 harus tetap melihat tombol aksi pengerjaan sisa');
        $this->assertEquals(50, $actionsM1[0]['quantity'], 'Sisa kuota pengerjaan M1 harus tepat 50 pcs');
        $this->assertEquals('Siap Diambil', $actionsM1[0]['target_status']);

        // C. getNextStatusLabel TIDAK boleh mengembalikan 'Selesai'
        $this->assertEquals('Siap Diambil', $wr->getNextStatusLabel($userM1));
        $this->assertEquals('Siap Diambil', $wr->getNextStatusLabel(null), 'Rekomendasi alur tiket harus mengarah ke sisa pengerjaan mesin');

        // D. Status badge menginformasikan parsial diterima 50/100
        $this->assertStringContainsString('50/100', $wr->status_badge['label']);

        // 5. M1 melanjutkan dan menyelesaikan sisa 50 pcs
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Siap Diambil',
            'quantity' => 50,
            'quantity_accumulated' => 100,
        ]);
        $wr->update(['status' => 'Siap Diambil', 'current_batch_qty' => 50]);

        // 6. Material Handling pickup sisa 50 pcs
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Menuju Pengaju',
            'quantity' => 50,
            'quantity_accumulated' => 100,
        ]);
        $wr->update(['status' => 'Menuju Pengaju', 'current_batch_qty' => 50]);

        // 7. Gudang menerima sisa 50 pcs (Total Diterima = 100 pcs -> Final Closed)
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Sudah Diambil',
            'quantity' => 50,
            'quantity_accumulated' => 100,
        ]);
        $wr->update([
            'status' => 'Sudah Diambil',
            'quantity_received' => 100,
            'is_final' => true,
            'current_batch_qty' => 0,
        ]);

        // VERIFIKASI FASE FINAL:
        $this->assertTrue($wr->is_final, 'Tiket harus berstatus final setelah 100 pcs tuntas diterima');
        $this->assertEmpty($wr->getAvailableActionsForUser($userM1), 'M1 tidak boleh memiliki aksi lagi setelah tiket final');
        $this->assertEmpty($wr->getAvailableActionsForUser($userMH), 'MH tidak boleh memiliki aksi lagi setelah tiket final');
        $this->assertEmpty($wr->getAvailableActionsForUser($userGudang), 'Gudang tidak boleh memiliki aksi lagi setelah tiket final');
        $this->assertEquals('Selesai', $wr->getNextStatusLabel(null));
        $this->assertEquals('Sudah Diambil (Final)', $wr->status_badge['label']);
    }

    public function test_machine_stops_completely_when_good_plus_defect_equals_total_supply(): void
    {
        $userM1 = new User([
            'name' => 'Operator M1',
            'work_area' => 'mesin_center',
            'department' => 'Dept M1',
        ]);

        $userMH = new User([
            'name' => 'Kurir MH',
            'work_area' => 'material_handling',
            'department' => 'Dept Logistic',
        ]);

        $userGudang = new User([
            'name' => 'Staf Gudang',
            'work_area' => 'gudang',
            'department' => 'Dept Gudang',
        ]);

        // Pengajuan 20 pcs untuk M1
        $wr = WorkRequest::create([
            'wri_number' => 'WRI-DEFECT-STOP-20',
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'M1',
            'quantity_requested' => 20,
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'status' => 'Dalam antrian',
            'is_final' => false,
        ]);

        // 1. M1 In: 20 pcs
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Proses: M1 (In)',
            'quantity' => 20,
            'quantity_accumulated' => 20,
        ]);
        $wr->update(['status' => 'Proses: M1 (In)']);

        // 2. M1 Out: 15 pcs unit bagus + 5 pcs defect mesin (Total: 20 pcs tuntas)
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'defect',
            'status_label' => 'Defect > Siap Diambil [M1]',
            'quantity' => 5,
            'quantity_accumulated' => 5,
            'notes' => 'Reject/Defect pada tahap Siap Diambil (M1): Plat gompal. Kuota total tiket dipotong 5 pcs menjadi 15 pcs.',
        ]);

        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Siap Diambil',
            'quantity' => 15,
            'quantity_accumulated' => 15,
        ]);

        $wr->update([
            'quantity_requested' => 15,
            'quantity_defect' => 5,
            'status' => 'Siap Diambil',
            'current_batch_qty' => 15,
        ]);

        // VERIFIKASI M1 BERHENTI (STOP):
        $bal = $wr->getStepBalance('M1', 0);
        $this->assertEquals(0, $bal['saldo_out'], 'Saldo out M1 harus 0 setelah 15 unit bagus + 5 defect selesai');
        $actionsM1 = $wr->getAvailableActionsForUser($userM1);
        $this->assertEmpty($actionsM1, 'M1 HARUS BERHENTI (tidak boleh ada tombol pengerjaan lagi)');

        // VERIFIKASI MATERIAL HANDLING MENERIMA 15 PCS:
        $this->assertEquals(15, $bal['waiting_pickup'], 'Barang siap angkut MH harus tepat 15 pcs');
        $actionsMH = $wr->getAvailableActionsForUser($userMH);
        $this->assertNotEmpty($actionsMH, 'Kurir MH harus memiliki aksi angkut');
        $this->assertEquals(15, $actionsMH[0]['quantity'], 'Jumlah yang diangkut kurir MH adalah 15 pcs');

        // 3. MH angkut 14 pcs bagus + 1 defect/hilang transit
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'defect',
            'status_label' => 'Defect > Menuju Pengaju [Gudang]',
            'quantity' => 1,
            'quantity_accumulated' => 6,
            'notes' => '1 unit hilang saat loading troli',
        ]);

        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Menuju Pengaju',
            'quantity' => 14,
            'quantity_accumulated' => 14,
        ]);

        $wr->update([
            'quantity_requested' => 14,
            'quantity_defect' => 6,
            'status' => 'Menuju Pengaju',
            'current_batch_qty' => 14,
        ]);

        // MH selesai angkut
        $actionsMHAfter = $wr->getAvailableActionsForUser($userMH);
        $this->assertEmpty($actionsMHAfter, 'Kurir MH harus berhenti setelah mengangkut seluruh barang siap');

        // 4. Gudang menerima 13 pcs bagus + 1 defect transit
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'defect',
            'status_label' => 'Defect > Sudah Diambil [Gudang]',
            'quantity' => 1,
            'quantity_accumulated' => 7,
            'notes' => '1 unit cacat terbentur troli',
        ]);

        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Sudah Diambil',
            'quantity' => 13,
            'quantity_accumulated' => 13,
        ]);

        $wr->update([
            'quantity_requested' => 13,
            'quantity_defect' => 7,
            'quantity_received' => 13,
            'status' => 'Sudah Diambil',
            'is_final' => true,
            'current_batch_qty' => 0,
        ]);

        // VERIFIKASI TIKET TUNTAS & SELESAI
        $this->assertTrue($wr->is_final, 'Tiket harus berstatus final');
        $this->assertEquals(13, $wr->quantity_received, 'Fisik yang diterima masuk stok gudang adalah 13 pcs');
        $this->assertEquals(7, $wr->quantity_defect, 'Total defect dari 3 stasiun adalah 7 pcs');
        $this->assertEquals(20, $wr->original_quantity_requested, 'Total order awal tetap tercatat 20 pcs');
        $this->assertEmpty($wr->getAvailableActionsForUser($userGudang));
    }
}
