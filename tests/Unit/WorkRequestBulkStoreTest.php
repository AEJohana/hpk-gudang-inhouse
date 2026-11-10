<?php

namespace Tests\Unit;

use App\Models\Component;
use App\Models\Department;
use App\Models\Location;
use App\Models\Machine;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkRequest;
use App\Models\WorkRequestLog;
use App\Models\WorkRequestStep;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkRequestBulkStoreTest extends TestCase
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

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('part_number')->nullable();
            $table->string('uom')->default('PCS');
            $table->decimal('minimum_stock', 12, 2)->default(0);
            $table->unsignedBigInteger('default_location_id')->nullable();
            $table->string('default_shelf_level')->nullable();
            $table->string('default_slot_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('machine_type');
            $table->string('location_workshop')->nullable();
            $table->string('status')->default('ready');
            $table->decimal('hourly_capacity', 8, 2)->default(10);
            $table->string('operator_default')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('zone_code')->nullable();
            $table->string('rack_number')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('work_requests', function (Blueprint $table) {
            $table->id();
            $table->string('wri_number')->unique();
            $table->unsignedBigInteger('component_id')->nullable();
            $table->unsignedBigInteger('requested_by_user_id')->nullable();
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
            $table->string('approval_pengaju')->default('Menunggu');
            $table->string('approval_penerima')->default('Menunggu');
            $table->unsignedBigInteger('target_warehouse_id')->nullable();
            $table->unsignedBigInteger('target_location_id')->nullable();
            $table->string('target_shelf_level')->nullable();
            $table->string('target_slot_number')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });

        Schema::create('work_request_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_request_id');
            $table->integer('step_number');
            $table->unsignedBigInteger('machine_id')->nullable();
            $table->string('process_name');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
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

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });

        // Seed basic dependencies
        Machine::create([
            'code' => 'MC-LC-01',
            'name' => 'Mesin Laser Cutting',
            'machine_type' => 'laser_cutting',
        ]);
        Warehouse::create([
            'code' => 'WH-01',
            'name' => 'Gudang Utama',
        ]);
        Location::forceCreate([
            'code' => 'LOC-01',
            'name' => 'Rak A1',
        ]);
    }

    public function test_bulk_store_creates_work_requests_for_all_cart_items(): void
    {
        $user = User::create([
            'name' => 'Pak Panji',
            'email' => 'panji@hydraxle.test',
            'password' => bcrypt('password'),
            'department' => 'Dept Gudang',
            'work_area' => 'gudang',
        ]);
        $this->actingAs($user);

        $comp1 = Component::create([
            'name' => 'MUR U BOLT ADR 25',
            'part_number' => 'CMRMUBOLTM20152',
            'uom' => 'PCS',
            'minimum_stock' => 5000,
            'is_active' => true,
        ]);

        $comp2 = Component::create([
            'name' => 'RING PER U BOLT M18',
            'part_number' => 'CRGVM18000000HT',
            'uom' => 'PCS',
            'minimum_stock' => 2000,
            'is_active' => true,
        ]);

        $cartData = [
            [
                'component_id' => $comp1->id,
                'quantity' => 1500,
                'department_name' => 'Dept Laser Cutting',
                'priority' => 'normal',
                'notes' => 'Potong pelat segera',
            ],
            [
                'component_id' => $comp2->id,
                'quantity' => 800,
                'department_name' => 'Dept Laser Cutting',
                'priority' => 'high',
                'notes' => 'Tekuk presisi',
            ],
        ];

        $request = new Request([
            'cart_data' => json_encode($cartData),
        ]);

        $controller = app(\App\Http\Controllers\WorkRequestController::class);
        $response = $controller->bulkStore($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertDatabaseCount('work_requests', 2);

        $wr1 = WorkRequest::where('component_id', $comp1->id)->first();
        $this->assertNotNull($wr1);
        $this->assertEquals(1500, $wr1->quantity_requested);
        $this->assertEquals('normal', $wr1->priority);
        $this->assertEquals('Potong pelat segera', $wr1->notes);
        $this->assertStringContainsString('WRI/DeptGudang/', $wr1->wri_number);
        $this->assertStringContainsString('/1/CMRMUBOLTM20152', $wr1->wri_number);
        $this->assertEquals('Dalam antrian', $wr1->status);

        $step1 = WorkRequestStep::where('work_request_id', $wr1->id)->first();
        $this->assertNotNull($step1);
        $this->assertEquals('Dept Laser Cutting', $step1->process_name);

        $wr2 = WorkRequest::where('component_id', $comp2->id)->first();
        $this->assertNotNull($wr2);
        $this->assertEquals(800, $wr2->quantity_requested);
        $this->assertEquals('high', $wr2->priority);
        $this->assertEquals('Tekuk presisi', $wr2->notes);
        $this->assertStringContainsString('/2/CRGVM18000000HT', $wr2->wri_number);
    }

    public function test_bulk_store_redirects_with_error_when_cart_is_empty(): void
    {
        $user = User::create([
            'name' => 'Staff Gudang',
            'email' => 'staff@hydraxle.test',
            'password' => bcrypt('password'),
            'department' => 'Dept Gudang',
        ]);
        $this->actingAs($user);

        $request = new Request([
            'cart_data' => '[]',
        ]);

        $controller = app(\App\Http\Controllers\WorkRequestController::class);
        $response = $controller->bulkStore($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('work-requests.safety-stock'), $response->headers->get('Location'));
        $this->assertDatabaseCount('work_requests', 0);
    }
}
