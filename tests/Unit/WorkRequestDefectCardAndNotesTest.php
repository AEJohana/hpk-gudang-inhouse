<?php

namespace Tests\Unit;

use App\Http\Controllers\WorkRequestController;
use App\Models\Component;
use App\Models\User;
use App\Models\WorkRequest;
use App\Models\WorkRequestLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkRequestDefectCardAndNotesTest extends TestCase
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

        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('work_area')->nullable();
            $table->string('department_name')->nullable();
            $table->string('department')->nullable();
            $table->boolean('can_access_all_departments')->default(true);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('item_code')->nullable();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->string('category')->default('Raw Material');
            $table->string('uom')->default('PCS');
            $table->decimal('total_stock', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('work_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('wri_number')->unique();
            $table->unsignedBigInteger('component_id');
            $table->unsignedBigInteger('requested_by_user_id')->nullable();
            $table->string('spk_reference')->nullable();
            $table->string('dept_pengaju')->default('Gudang');
            $table->string('dept_penerima')->default('Laser');
            $table->decimal('quantity_requested', 15, 2);
            $table->decimal('quantity_defect', 15, 2)->default(0);
            $table->decimal('quantity_received', 15, 2)->default(0);
            $table->decimal('current_batch_qty', 15, 2)->default(0);
            $table->string('status')->default('draft');
            $table->string('current_process_status')->nullable();
            $table->string('approval_pengaju')->default('Diterima');
            $table->string('approval_penerima')->default('Diterima');
            $table->string('assigned_operator_name')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('received_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('work_request_steps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_request_id');
            $table->integer('step_number');
            $table->string('process_name');
            $table->string('status')->default('pending');
            $table->string('operator_name')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('work_request_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_request_id');
            $table->string('action_type')->default('process');
            $table->string('status_label');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('quantity_accumulated', 15, 2)->default(0);
            $table->string('operator_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('module')->nullable();
            $table->string('action_type')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    public function test_custom_user_defect_reason_is_saved_exactly_as_entered(): void
    {
        $comp = Component::create([
            'item_code' => 'RAW-001',
            'part_number' => 'PN-RAW-001',
            'name' => 'Braket Depan',
            'uom' => 'PCS',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WRI/2026/TEST-001',
            'component_id' => $comp->id,
            'quantity_requested' => 15,
            'status' => 'Menuju Pengaju',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
        ]);

        $user = User::create([
            'name' => 'Pak Fajar',
            'username' => 'fajar_mh',
            'password' => bcrypt('password'),
            'work_area' => 'material_handling',
            'department_name' => 'Material Handling',
            'can_access_all_departments' => true,
        ]);
        $this->actingAs($user);

        $request = Request::create('/work-requests/' . $wr->id . '/process-action', 'POST', [
            'action_type' => 'process',
            'target_status' => 'Menuju Pengaju',
            'process_qty' => 10,
            'defect_qty' => 5,
            'defect_reason' => 'cacacat fisik terbentur troli',
        ]);

        $controller = app(WorkRequestController::class);
        $controller->processAction($request, $wr);

        $defectLog = WorkRequestLog::where('work_request_id', $wr->id)
            ->where('action_type', 'defect')
            ->first();

        $this->assertNotNull($defectLog);
        $this->assertEquals('cacacat fisik terbentur troli', $defectLog->notes);
        $this->assertEquals(5, (float) $defectLog->quantity);

        // Work request active quota should be reduced by defect
        $wr->refresh();
        $this->assertEquals(10, (float) $wr->quantity_requested);
        $this->assertEquals(5, (float) $wr->quantity_defect);
    }

    public function test_empty_defect_reason_uses_concise_default_notes(): void
    {
        $comp = Component::create([
            'item_code' => 'RAW-002',
            'part_number' => 'PN-RAW-002',
            'name' => 'Plat Bawah',
            'uom' => 'PCS',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WRI/2026/TEST-002',
            'component_id' => $comp->id,
            'quantity_requested' => 20,
            'status' => 'Sudah Diambil',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => false,
        ]);

        $user = User::create([
            'name' => 'Pak Rudi',
            'username' => 'rudi_gudang',
            'password' => bcrypt('password'),
            'work_area' => 'gudang',
            'department_name' => 'Gudang',
            'can_access_all_departments' => true,
        ]);
        $this->actingAs($user);

        $request = Request::create('/work-requests/' . $wr->id . '/process-action', 'POST', [
            'action_type' => 'defect',
            'defect_qty' => 5,
            'defect_reason' => '', // Empty
        ]);

        $controller = app(WorkRequestController::class);
        $controller->processAction($request, $wr);

        $defectLog = WorkRequestLog::where('work_request_id', $wr->id)
            ->where('action_type', 'defect')
            ->first();

        $this->assertNotNull($defectLog);
        $this->assertEquals('Cacat / reject fisik', $defectLog->notes);
    }

    public function test_hundred_percent_defect_auto_closes_ticket_without_ghost_process_log(): void
    {
        $comp = Component::create([
            'item_code' => 'RAW-003',
            'part_number' => 'PN-RAW-003',
            'name' => 'Pipa Hollow',
            'uom' => 'PCS',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WRI/2026/TEST-003',
            'component_id' => $comp->id,
            'quantity_requested' => 10,
            'status' => 'Menuju Pengaju',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
        ]);

        $user = User::create([
            'name' => 'Pak Fajar',
            'username' => 'fajar_mh_2',
            'password' => bcrypt('password'),
            'work_area' => 'material_handling',
            'department_name' => 'Material Handling',
            'can_access_all_departments' => true,
        ]);
        $this->actingAs($user);

        // Entire 10 pcs reported as defect
        $request = Request::create('/work-requests/' . $wr->id . '/process-action', 'POST', [
            'action_type' => 'process',
            'target_status' => 'Menuju Pengaju',
            'process_qty' => 10,
            'defect_qty' => 10,
            'defect_reason' => 'cacacat semua 10 pcs rusak total',
        ]);

        $controller = app(WorkRequestController::class);
        $controller->processAction($request, $wr);

        $wr->refresh();
        $this->assertTrue((bool) $wr->is_final, 'Ticket must be auto-closed when 100% defect');
        $this->assertEquals('Selesai (Defect Penuh)', $wr->status);
        $this->assertEquals(0, (float) $wr->quantity_requested);
        $this->assertEquals(10, (float) $wr->quantity_defect);

        // Verify no ghost process log with 0 quantity was created
        $processLogs = WorkRequestLog::where('work_request_id', $wr->id)
            ->where('action_type', 'process')
            ->get();
        $this->assertCount(0, $processLogs, 'Should not create ghost process log when all units are defective');

        // Verify defect log exists with exact reason
        $defectLog = WorkRequestLog::where('work_request_id', $wr->id)
            ->where('action_type', 'defect')
            ->first();
        $this->assertNotNull($defectLog);
        $this->assertEquals('cacacat semua 10 pcs rusak total', $defectLog->notes);
        $this->assertEquals(10, (float) $defectLog->quantity);
    }

    public function test_detail_modal_template_has_dedicated_defect_card_in_gudang_logs(): void
    {
        $modalPath = realpath(__DIR__ . '/../../resources/views/work_requests/partials/detail_modal.blade.php');
        $this->assertNotFalse($modalPath);
        $content = file_get_contents($modalPath);

        // 1. Red defect card template in getGudangLogs
        $this->assertStringContainsString('template x-if="gLog.is_defect"', $content);
        $this->assertStringContainsString('DEFECT / REJECT SAAT PICKUP MH', $content);
        $this->assertStringContainsString('DEFECT / REJECT PENERIMAAN GUDANG', $content);
        $this->assertStringContainsString("'-' + gLog.qty + ' ' + detailData.uom", $content);

        // 2. Mutual exclusion for normal blue and green cards
        $this->assertStringContainsString('template x-if="!gLog.is_defect && gLog.is_mh"', $content);
        $this->assertStringContainsString('template x-if="!gLog.is_defect && !gLog.is_mh"', $content);

        // 3. Header badge handles totalQty === 0
        $this->assertStringContainsString('Semua Defect (0 Pcs)', $content);
    }

    public function test_format_defect_notes_helper_cleans_legacy_boilerplates(): void
    {
        $indexPath = realpath(__DIR__ . '/../../resources/views/work_requests/index.blade.php');
        $this->assertNotFalse($indexPath);
        $content = file_get_contents($indexPath);

        $this->assertStringContainsString('formatDefectNotes(notes)', $content);
        $this->assertStringContainsString('Kuota', $content);
        $this->assertStringContainsString('dipotong', $content);
    }
}
