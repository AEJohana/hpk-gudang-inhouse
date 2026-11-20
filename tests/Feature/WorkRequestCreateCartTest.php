<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Department;
use App\Models\Machine;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkRequest;
use App\Models\WorkRequestStep;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkRequestCreateCartTest extends TestCase
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
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('work_area')->nullable();
            $table->string('department')->nullable();
            $table->boolean('can_access_all_departments')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('part_number')->nullable();
            $table->string('uom')->default('PCS');
            $table->string('category')->nullable();
            $table->string('specification')->nullable();
            $table->decimal('minimum_stock', 12, 2)->default(0);
            $table->unsignedBigInteger('default_location_id')->nullable();
            $table->string('default_shelf_level')->nullable();
            $table->string('default_slot_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('component_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('machine_type');
            $table->string('status')->default('ready');
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->string('zone_code')->nullable();
            $table->string('zone_name')->nullable();
            $table->string('aisle')->nullable();
            $table->string('rack_number')->nullable();
            $table->string('storage_type')->nullable();
            $table->string('rack_code')->nullable();
            $table->string('bin_level')->nullable();
            $table->integer('total_levels')->default(1);
            $table->integer('slots_per_level')->default(1);
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('max_capacity')->default(100);
            $table->integer('grid_x')->default(0);
            $table->integer('grid_y')->default(0);
            $table->integer('grid_w')->default(1);
            $table->integer('grid_h')->default(1);
            $table->string('color')->nullable();
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

        // Seed basic fixtures
        Department::create(['code' => '11', 'name' => 'Dept Gudang']);
        Department::create(['code' => '01', 'name' => 'Dept Laser Cutting']);
        Department::create(['code' => '02', 'name' => 'Dept M1']);
        Department::create(['code' => '03', 'name' => 'Dept UNP']);

        Machine::create(['code' => 'MC-LC-01', 'name' => 'Mesin Laser Cutting', 'machine_type' => 'laser_cutting']);
        Machine::create(['code' => 'MC-M1-01', 'name' => 'Mesin M1', 'machine_type' => 'm1']);
        Warehouse::create(['code' => 'WH-01', 'name' => 'Gudang Utama']);
    }

    public function test_create_work_request_view_renders_cart_system_and_scan_elements(): void
    {
        Permission::create(['name' => 'work-request.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'work-request.create', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'Operator Gudang',
            'username' => 'op_gudang',
            'department' => 'Dept Gudang',
            'work_area' => 'gudang',
            'password' => bcrypt('password'),
        ]);
        $user->givePermissionTo(['work-request.view', 'work-request.create']);

        $component = Component::create([
            'name' => 'HANDLE PIN LOCK D7',
            'part_number' => 'CSBA30100360000',
            'uom' => 'PCS',
            'minimum_stock' => 100,
        ]);

        $this->withoutExceptionHandling();
        $response = $this->actingAs($user)->get(route('work-requests.create'));

        $response->assertStatus(200);
        $response->assertSee('Membuat Work Request Baru');
        $response->assertSee('Pemohon');
        $response->assertSee('Tanggal Order');
        $response->assertSee('Cari Komponen');
        $response->assertSee('Scan');
        $response->assertSee('Tambah Komponen Baru');
        $response->assertSee('INPUT KOMPONEN BARU');
        $response->assertSee('Tipe Material');
        $response->assertSee('Tampilkan Preview');
        $response->assertSee('Pilih Dept (Urut)');
        $response->assertSee('Tambah ke List');
        $response->assertSee('Daftar Item Siap Kirim');
        $response->assertSee('Kirim Work Request');
    }

    public function test_bulk_store_creates_work_requests_with_multi_step_routing(): void
    {
        Permission::findOrCreate('work-request.view', 'web');
        Permission::findOrCreate('work-request.create', 'web');
        $user = User::create([
            'name' => 'Staff Gudang',
            'username' => 'staff_gudang',
            'department' => 'Dept Gudang',
            'work_area' => 'gudang',
            'password' => bcrypt('password'),
        ]);
        $user->givePermissionTo(['work-request.view', 'work-request.create']);

        $comp1 = Component::create([
            'name' => 'BRACKET SUBFRAME D7',
            'part_number' => 'CSBP10100200',
            'uom' => 'PCS',
        ]);

        $comp2 = Component::create([
            'name' => 'PIN ENGSEL PINTU',
            'part_number' => 'CPIN20200100',
            'uom' => 'PCS',
        ]);

        $cart = [
            [
                'component_id' => $comp1->id,
                'quantity' => 100,
                'department_name' => 'Dept Laser Cutting',
                'notes' => 'Potong segera',
            ],
            [
                'component_id' => $comp2->id,
                'quantity' => 50,
                'department_name' => 'Dept Laser Cutting -> Dept M1',
                'notes' => 'Urutan laser lalu bubut M1',
            ],
        ];

        $response = $this->actingAs($user)->post(route('work-requests.bulk-store'), [
            'dept_pengaju' => 'Dept Gudang',
            'cart_data' => json_encode($cart),
        ]);

        $response->assertRedirect(route('work-requests.index'));
        $this->assertDatabaseCount('work_requests', 2);

        $wr1 = WorkRequest::where('component_id', $comp1->id)->first();
        $this->assertNotNull($wr1);
        $this->assertEquals(100, $wr1->quantity_requested);
        $this->assertEquals('Dept Laser Cutting', $wr1->dept_penerima);
        $this->assertEquals('Dept Laser Cutting', $wr1->routing_text);

        $wr2 = WorkRequest::where('component_id', $comp2->id)->first();
        $this->assertNotNull($wr2);
        $this->assertEquals(50, $wr2->quantity_requested);
        $this->assertEquals('Dept Laser Cutting', $wr2->dept_penerima);
        $this->assertEquals('Dept Laser Cutting -> Dept M1', $wr2->routing_text);

        // Verify steps for wr2
        $steps = WorkRequestStep::where('work_request_id', $wr2->id)->orderBy('step_number')->get();
        $this->assertCount(2, $steps);
        $this->assertEquals('Dept Laser Cutting', $steps[0]->process_name);
        $this->assertEquals('Dept M1', $steps[1]->process_name);
    }
}
