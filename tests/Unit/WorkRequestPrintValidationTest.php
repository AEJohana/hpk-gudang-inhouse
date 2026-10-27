<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WorkRequest;
use App\Models\WorkRequestLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkRequestPrintValidationTest extends TestCase
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

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('work_area')->nullable();
            $table->string('department')->nullable();
            $table->boolean('can_access_all_departments')->default(false);
            $table->timestamps();
        });

        Schema::create('work_requests', function (Blueprint $table) {
            $table->id();
            $table->string('wri_number')->unique();
            $table->unsignedBigInteger('component_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('dept_pengaju')->default('Gudang');
            $table->string('dept_penerima')->default('Laser');
            $table->string('routing_text')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('Dalam antrian');
            $table->string('current_process_status')->nullable();
            $table->decimal('quantity_requested', 12, 2)->default(0);
            $table->decimal('quantity_produced', 12, 2)->default(0);
            $table->decimal('quantity_received', 12, 2)->default(0);
            $table->decimal('quantity_defect', 12, 2)->default(0);
            $table->decimal('current_batch_qty', 12, 2)->default(0);
            $table->string('approval_pengaju')->default('Menunggu');
            $table->string('approval_penerima')->default('Menunggu');
            $table->string('target_specific_location_code')->nullable();
            $table->string('assigned_operator_name')->nullable();
            $table->string('spk_reference')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });

        Schema::create('work_request_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_request_id');
            $table->string('action_type', 50);
            $table->string('status_label', 150);
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('quantity_accumulated', 12, 2)->default(0);
            $table->string('operator_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function test_wr_cannot_be_printed_if_approval_pengaju_is_pending(): void
    {
        $wr = WorkRequest::create([
            'wri_number' => 'WRI-TEST-01',
            'approval_pengaju' => 'Menunggu',
            'approval_penerima' => 'Diterima',
            'assigned_operator_name' => 'Pak Fajar',
        ]);

        $this->assertFalse($wr->isPrintable());
        $this->assertStringContainsString('Pengaju', $wr->getPrintValidationMessage());
    }

    public function test_wr_cannot_be_printed_if_approval_penerima_is_pending(): void
    {
        $wr = WorkRequest::create([
            'wri_number' => 'WRI-TEST-02',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Menunggu',
            'assigned_operator_name' => 'Pak Fajar',
        ]);

        $this->assertFalse($wr->isPrintable());
        $this->assertStringContainsString('Penerima', $wr->getPrintValidationMessage());
    }

    public function test_wr_cannot_be_printed_if_operator_is_not_assigned(): void
    {
        $wr = WorkRequest::create([
            'wri_number' => 'WRI-TEST-03',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'assigned_operator_name' => null,
        ]);

        $this->assertFalse($wr->isPrintable());
        $this->assertStringContainsString('Operator', $wr->getPrintValidationMessage());
    }

    public function test_wr_is_printable_when_all_three_criteria_are_met(): void
    {
        $wr = WorkRequest::create([
            'wri_number' => 'WRI-TEST-VALID',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'assigned_operator_name' => 'Pak Fajar M1',
        ]);

        $this->assertTrue($wr->isPrintable());
        $this->assertEquals('Dokumen valid & siap dicetak.', $wr->getPrintValidationMessage());

        $printableList = WorkRequest::printable()->pluck('wri_number')->toArray();
        $this->assertContains('WRI-TEST-VALID', $printableList);
    }

    public function test_print_count_and_reprint_audit_log_tracking(): void
    {
        $wr = WorkRequest::create([
            'wri_number' => 'WRI-TEST-PRINT-LOG',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'assigned_operator_name' => 'Pak Joko',
        ]);

        $this->assertEquals(0, $wr->print_count);

        // Simulasi cetak perdana
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'printed',
            'status_label' => 'Dokumen Dicetak',
            'operator_name' => 'Admin Gudang',
        ]);

        $this->assertEquals(1, $wr->fresh()->print_count);

        // Simulasi cetak ulang kedua
        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'printed',
            'status_label' => 'Cetak Ulang (#2)',
            'operator_name' => 'Admin Gudang',
        ]);

        $this->assertEquals(2, $wr->fresh()->print_count);
    }

    public function test_qr_code_svg_and_data_uri_generated_locally(): void
    {
        $wr = WorkRequest::create([
            'wri_number' => 'WRI/20261027/0099',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'assigned_operator_name' => 'Pak Joko',
        ]);

        $svg = $wr->qr_code_svg;
        $this->assertStringContainsString('<svg', $svg);

        $dataUri = $wr->qr_code_data_uri;
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUri);
    }

    public function test_print_queue_query_eager_loads_logs_without_relation_error(): void
    {
        $wr = WorkRequest::create([
            'wri_number' => 'WRI/20261027/QUEUE-01',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'assigned_operator_name' => 'Pak Bambang',
        ]);

        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'approval',
            'status_label' => 'Disetujui Ka. Pengaju',
            'operator_name' => 'Pak Panji',
        ]);

        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'approval',
            'status_label' => 'Disetujui Ka. Penerima',
            'operator_name' => 'Pak Andreas',
        ]);

        // Eager load with logs
        $fetched = WorkRequest::with('logs')->printable()->find($wr->id);
        $this->assertNotNull($fetched);
        $this->assertTrue($fetched->relationLoaded('logs'));
        $this->assertEquals('Pak Panji', $fetched->approval_pengaju_log?->operator_name);
        $this->assertEquals('Pak Andreas', $fetched->approval_penerima_log?->operator_name);
    }
}
