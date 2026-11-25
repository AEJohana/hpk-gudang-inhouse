<?php

namespace Tests\Unit;

use App\Http\Controllers\WorkStationSupplyController;
use App\Models\Component;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkRequest;
use App\Models\WorkRequestLog;
use App\Models\WorkRequestStep;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class WorkStationSupplyQrAndProcessModalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('settings');
        Schema::create('settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('work_request_logs');
        Schema::create('work_request_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_request_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action_type')->nullable();
            $table->string('status_label')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('operator_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('work_request_steps');
        Schema::create('work_request_steps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_request_id');
            $table->integer('step_number')->default(1);
            $table->string('process_name');
            $table->string('status')->default('pending');
            $table->string('operator_name')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('work_requests');
        Schema::create('work_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('wri_number')->unique();
            $table->unsignedBigInteger('component_id')->nullable();
            $table->unsignedBigInteger('requested_by_user_id')->nullable();
            $table->unsignedBigInteger('target_location_id')->nullable();
            $table->string('target_specific_location_code')->nullable();
            $table->string('dept_pengaju')->nullable();
            $table->string('dept_penerima')->nullable();
            $table->decimal('quantity_requested', 12, 2)->default(10);
            $table->decimal('quantity_produced', 12, 2)->default(0);
            $table->decimal('quantity_received', 12, 2)->default(0);
            $table->decimal('quantity_defect', 12, 2)->default(0);
            $table->decimal('current_batch_qty', 12, 2)->default(0);
            $table->string('status')->default('Dalam antrian');
            $table->string('approval_pengaju')->default('Diterima');
            $table->string('approval_penerima')->default('Diterima');
            $table->string('assigned_operator_name')->nullable();
            $table->string('priority')->default('Normal');
            $table->string('spk_reference')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });

        Schema::dropIfExists('components');
        Schema::create('components', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('part_number')->nullable();
            $table->string('name');
            $table->string('uom')->default('PCS');
            $table->string('source')->default('master');
            $table->string('status')->default('active');
            $table->string('dimension_formatted')->nullable();
            $table->unsignedBigInteger('default_location_id')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('locations');
        Schema::create('locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('work_area')->default('material_handling');
            $table->string('department')->default('Dept Gudang');
            $table->timestamps();
        });
    }

    public function test_edited_blade_templates_compile_without_syntax_errors(): void
    {
        $views = [
            'resources/views/work_station_supplies/index.blade.php',
            'resources/views/work_requests/partials/process_modal.blade.php',
            'resources/views/work_requests/partials/qr_zoom_modal.blade.php',
            'resources/views/work_requests/partials/_table_content.blade.php',
            'resources/views/work_requests/show.blade.php',
            'resources/views/work_requests/index.blade.php',
        ];

        foreach ($views as $relativePath) {
            $fullPath = base_path($relativePath);
            $this->assertFileExists($fullPath);
            $compiled = Blade::compileString(file_get_contents($fullPath));
            $this->assertNotEmpty($compiled, "Blade compile returned empty for {$relativePath}");
        }
    }

    public function test_material_handling_controller_passes_is_wr_scan_required_and_qr_url(): void
    {
        Cache::forget('setting_wr_scan_required');
        Setting::create(['key' => 'wr_scan_required', 'value' => '1']);

        $comp = Component::create([
            'part_number' => 'CSAA50550200000',
            'name' => 'AS DRAT',
            'uom' => 'PCS',
            'source' => 'master',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WR/DEPT GUDANG/20261124/1/CSAA50550200000/1',
            'component_id' => $comp->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'quantity_requested' => 111,
            'status' => 'Siap Diambil',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => false,
        ]);

        WorkRequestStep::create([
            'work_request_id' => $wr->id,
            'step_number' => 1,
            'process_name' => 'Dept M1',
            'status' => 'completed',
        ]);

        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'action_type' => 'process',
            'status_label' => 'Proses: Dept M1 (Out)',
            'quantity' => 111,
        ]);

        $controller = new WorkStationSupplyController();
        $view = $controller->index(Request::create('/work-station-supplies', 'GET'));
        $data = $view->getData();

        $this->assertTrue($data['isWrScanRequired']);
        $this->assertCount(1, $data['finalWaiting']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $data['finalWaiting'][0]['qr_url']);
        $this->assertSame('PCS', $data['finalWaiting'][0]['uom']);
    }

    public function test_material_handling_view_contains_camera_gun_and_photo_fallback_qr_scanner(): void
    {
        $content = file_get_contents(resource_path('views/work_station_supplies/index.blade.php'));

        $this->assertStringContainsString('name="scanned_wri_code"', $content);
        $this->assertStringContainsString('openActionCameraModal()', $content);
        $this->assertStringContainsString('mh-action-camera-reader', $content);
        $this->assertStringContainsString('scanActionQrFile($event)', $content);
        $this->assertStringContainsString('attachActionGunScannerListener()', $content);
        $this->assertStringContainsString('Pindai QR Lembar WRI Dahulu', $content);
        $this->assertStringContainsString('scanMhQrFile($event)', $content);
    }

    public function test_process_modal_and_payload_include_clickable_wr_qr_and_zoom_z_index(): void
    {
        $tableContent = file_get_contents(resource_path('views/work_requests/partials/_table_content.blade.php'));
        $wrController = file_get_contents(app_path('Http/Controllers/WorkRequestController.php'));
        $processModal = file_get_contents(resource_path('views/work_requests/partials/process_modal.blade.php'));
        $showView = file_get_contents(resource_path('views/work_requests/show.blade.php'));
        $qrZoomModal = file_get_contents(resource_path('views/work_requests/partials/qr_zoom_modal.blade.php'));

        $this->assertStringContainsString("'qrUrl' => \$wri->qr_code_data_uri", $tableContent);
        $this->assertStringContainsString("'qrUrl' => \$targetWr->qr_code_data_uri", $wrController);
        $this->assertStringContainsString('openQrZoom(processData.qrUrl, processData.wriNumber, processData.componentName)', $processModal);
        $this->assertStringContainsString('processData.qrUrl', $processModal);
        $this->assertStringContainsString('@include(\'work_requests.partials.qr_zoom_modal\')', $showView);
        $this->assertStringContainsString('$workRequest->qr_code_data_uri', $showView);
        $this->assertStringContainsString('z-[99999]', $qrZoomModal);
    }
}
