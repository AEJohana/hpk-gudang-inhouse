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

    public function test_date_range_preset_filtering(): void
    {
        $todayWr = WorkRequest::create([
            'wri_number' => 'WRI/20261027/TODAY',
        ]);

        $twoDaysAgoWr = WorkRequest::create([
            'wri_number' => 'WRI/20261027/2DAYS',
        ]);
        WorkRequest::where('id', $twoDaysAgoWr->id)->update(['created_at' => now()->subDays(2)]);

        $fiveDaysAgoWr = WorkRequest::create([
            'wri_number' => 'WRI/20261027/5DAYS',
        ]);
        WorkRequest::where('id', $fiveDaysAgoWr->id)->update(['created_at' => now()->subDays(5)]);

        $fortyDaysAgoWr = WorkRequest::create([
            'wri_number' => 'WRI/20261027/40DAYS',
        ]);
        WorkRequest::where('id', $fortyDaysAgoWr->id)->update(['created_at' => now()->subDays(40)]);

        // Preset 'today'
        $todayResults = WorkRequest::dateRange('today')->pluck('id')->toArray();
        $this->assertContains($todayWr->id, $todayResults);
        $this->assertNotContains($twoDaysAgoWr->id, $todayResults);
        $this->assertNotContains($fiveDaysAgoWr->id, $todayResults);
        $this->assertNotContains($fortyDaysAgoWr->id, $todayResults);

        // Preset '3days'
        $threeDaysResults = WorkRequest::dateRange('3days')->pluck('id')->toArray();
        $this->assertContains($todayWr->id, $threeDaysResults);
        $this->assertContains($twoDaysAgoWr->id, $threeDaysResults);
        $this->assertNotContains($fiveDaysAgoWr->id, $threeDaysResults);
        $this->assertNotContains($fortyDaysAgoWr->id, $threeDaysResults);

        // Preset '7days'
        $sevenDaysResults = WorkRequest::dateRange('7days')->pluck('id')->toArray();
        $this->assertContains($todayWr->id, $sevenDaysResults);
        $this->assertContains($twoDaysAgoWr->id, $sevenDaysResults);
        $this->assertContains($fiveDaysAgoWr->id, $sevenDaysResults);
        $this->assertNotContains($fortyDaysAgoWr->id, $sevenDaysResults);

        // Preset 'all'
        $allResults = WorkRequest::dateRange('all')->pluck('id')->toArray();
        $this->assertContains($todayWr->id, $allResults);
        $this->assertContains($twoDaysAgoWr->id, $allResults);
        $this->assertContains($fiveDaysAgoWr->id, $allResults);
        $this->assertContains($fortyDaysAgoWr->id, $allResults);
    }

    public function test_custom_date_range_filtering(): void
    {
        $todayWr = WorkRequest::create([
            'wri_number' => 'WRI/20261027/CUSTOM-01',
        ]);

        $threeDaysAgoWr = WorkRequest::create([
            'wri_number' => 'WRI/20261027/CUSTOM-02',
        ]);
        WorkRequest::where('id', $threeDaysAgoWr->id)->update(['created_at' => now()->subDays(3)]);

        $tenDaysAgoWr = WorkRequest::create([
            'wri_number' => 'WRI/20261027/CUSTOM-03',
        ]);
        WorkRequest::where('id', $tenDaysAgoWr->id)->update(['created_at' => now()->subDays(10)]);

        // Range between 5 days ago and 2 days ago
        $customResults = WorkRequest::dateRange('custom', now()->subDays(5)->toDateString(), now()->subDays(2)->toDateString())
            ->pluck('id')
            ->toArray();

        $this->assertNotContains($todayWr->id, $customResults);
        $this->assertContains($threeDaysAgoWr->id, $customResults);
        $this->assertNotContains($tenDaysAgoWr->id, $customResults);

        // Start date only
        $startOnlyResults = WorkRequest::dateRange('custom', now()->subDays(2)->toDateString(), null)
            ->pluck('id')
            ->toArray();
        $this->assertContains($todayWr->id, $startOnlyResults);
        $this->assertNotContains($threeDaysAgoWr->id, $startOnlyResults);

        // End date only
        $endOnlyResults = WorkRequest::dateRange('custom', null, now()->subDays(4)->toDateString())
            ->pluck('id')
            ->toArray();
        $this->assertNotContains($todayWr->id, $endOnlyResults);
        $this->assertNotContains($threeDaysAgoWr->id, $endOnlyResults);
        $this->assertContains($tenDaysAgoWr->id, $endOnlyResults);
    }
}
