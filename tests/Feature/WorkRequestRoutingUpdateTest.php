<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkRequest;
use App\Models\WorkRequestStep;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkRequestRoutingUpdateTest extends TestCase
{
    protected User $admin;
    protected User $operatorPengaju;
    protected User $operatorLain;
    protected Department $deptGudang;
    protected Department $deptLaser;
    protected Department $deptM1;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles & Permissions Tables
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

        // 2. Departments Table
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('alias')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Users Table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('phone')->nullable();
            $table->string('role')->default('operator');
            $table->string('work_area')->nullable();
            $table->string('department')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->boolean('can_access_all_departments')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Components Table
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('part_number', 50)->nullable()->unique();
            $table->string('name', 255);
            $table->string('category', 50)->nullable();
            $table->unsignedBigInteger('component_category_id')->nullable();
            $table->string('uom', 20)->default('PCS');
            $table->string('length_mm', 100)->nullable();
            $table->string('width_or_diameter_mm', 100)->nullable();
            $table->string('thickness_or_height_mm', 100)->nullable();
            $table->text('specification')->nullable();
            $table->decimal('minimum_stock', 10, 2)->default(0);
            $table->decimal('maximum_stock', 10, 2)->nullable();
            $table->unsignedBigInteger('default_location_id')->nullable();
            $table->string('default_shelf_level', 10)->nullable();
            $table->string('default_slot_number', 10)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('qr_code_payload', 255)->nullable();
            $table->string('status', 50)->default('active');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Machines Table
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('machine_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Work Requests & Steps & Logs
        Schema::create('work_requests', function (Blueprint $table) {
            $table->id();
            $table->string('wri_number', 50)->unique();
            $table->foreignId('component_id');
            $table->foreignId('requested_by_user_id');
            $table->string('dept_pengaju', 100)->nullable();
            $table->string('dept_penerima', 100)->nullable();
            $table->string('routing_text', 255)->nullable();
            $table->string('approval_pengaju', 50)->default('Diterima');
            $table->string('approval_penerima', 50)->default('Diterima');
            $table->unsignedBigInteger('target_warehouse_id')->nullable();
            $table->unsignedBigInteger('target_location_id')->nullable();
            $table->string('target_shelf_level', 10)->nullable();
            $table->string('target_slot_number', 10)->nullable();
            $table->decimal('quantity_requested', 10, 2);
            $table->decimal('quantity_produced', 10, 2)->default(0);
            $table->decimal('quantity_received', 10, 2)->default(0);
            $table->decimal('quantity_defect', 10, 2)->default(0);
            $table->boolean('is_final')->default(false);
            $table->string('assigned_operator_name', 100)->nullable();
            $table->string('priority', 50)->default('normal');
            $table->string('status', 100)->default('Dalam antrian');
            $table->string('current_process_status', 100)->default('Dalam antrian');
            $table->date('due_date')->nullable();
            $table->string('spk_reference', 100)->nullable();
            $table->text('notes')->nullable();
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
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
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

        Schema::create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });

        // Setup Master Data
        $adminRole = Role::create(['name' => 'admin_gudang']);
        $operatorRole = Role::create(['name' => 'operator']);
        $viewPermission = \Spatie\Permission\Models\Permission::create(['name' => 'work-request.view']);
        $adminRole->givePermissionTo($viewPermission);
        $operatorRole->givePermissionTo($viewPermission);

        $this->deptGudang = Department::create(['code' => '11', 'name' => 'Dept Gudang']);
        $this->deptLaser = Department::create(['code' => '1', 'name' => 'Dept Laser Cutting']);
        $this->deptM1 = Department::create(['code' => '2', 'name' => 'Dept M1']);

        $this->admin = User::create([
            'name' => 'Admin Gudang',
            'username' => 'admin',
            'role' => 'admin_gudang',
            'department_id' => $this->deptGudang->id,
            'department' => 'Dept Gudang',
            'can_access_all_departments' => true,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->admin->syncRoles([$adminRole]);

        $this->operatorPengaju = User::create([
            'name' => 'Operator Gudang',
            'username' => 'op_gudang',
            'role' => 'operator',
            'department_id' => $this->deptGudang->id,
            'department' => 'Dept Gudang',
            'can_access_all_departments' => false,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->operatorPengaju->syncRoles([$operatorRole]);

        $this->operatorLain = User::create([
            'name' => 'Operator Lain',
            'username' => 'op_lain',
            'role' => 'operator',
            'department_id' => $this->deptLaser->id,
            'department' => 'Dept Laser Cutting',
            'can_access_all_departments' => false,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->operatorLain->syncRoles([$operatorRole]);
    }

    public function test_user_can_update_routing_on_new_component_before_processing(): void
    {
        // 1. Buat komponen baru (non-master / pending catalog / part number berawalan NEW-)
        $newComponent = Component::create([
            'name' => 'Bracket Ad-Hoc Baru',
            'part_number' => 'NEW-20261120-ABCD',
            'specification' => 'Komponen baru didaftarkan via form Work Request',
            'status' => 'pending_catalog',
            'uom' => 'PCS',
        ]);

        // 2. Buat Work Request yang awalnya ke Dept M1
        $wr = WorkRequest::create([
            'wri_number' => 'WR/Gudang/20261120/001/Baru/1',
            'component_id' => $newComponent->id,
            'requested_by_user_id' => $this->operatorPengaju->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'assigned_operator_name' => 'Operator M1',
            'quantity_requested' => 16,
            'quantity_produced' => 0,
            'quantity_received' => 0,
            'status' => 'Dalam antrian',
            'is_final' => false,
        ]);

        WorkRequestStep::create([
            'work_request_id' => $wr->id,
            'step_number' => 1,
            'machine_id' => null,
            'process_name' => 'Dept M1',
            'status' => 'pending',
        ]);

        $this->assertTrue($wr->isNewPart());
        $this->assertTrue($wr->canUserEditRouting($this->operatorPengaju));

        // 3. Pengaju mengubah alur menjadi multi-tahap: Dept Laser Cutting -> Dept M1
        $response = $this->actingAs($this->operatorPengaju)->put(route('work-requests.update-routing', $wr), [
            'target_departments' => [
                ['department_name' => 'Dept Laser Cutting', 'notes' => 'Potong pelat profil'],
                ['department_name' => 'Dept M1', 'notes' => 'Finishing lubang bor'],
            ],
            'reason' => 'Part baru butuh pemotongan laser terlebih dahulu',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 4. Verifikasi perubahan pada basis data
        $wr->refresh();
        $this->assertEquals('Dept Laser Cutting -> Dept M1', $wr->routing_text);
        $this->assertEquals('Dept Laser Cutting', $wr->dept_penerima);
        // Persetujuan penerima wajib di-reset ke Menunggu karena stasiun awal berubah dari M1 ke Laser
        $this->assertEquals('Menunggu', $wr->approval_penerima);
        $this->assertNull($wr->assigned_operator_name);

        // Verifikasi langkah pengerjaan (steps)
        $steps = $wr->steps()->orderBy('step_number')->get();
        $this->assertCount(2, $steps);
        $this->assertEquals('Dept Laser Cutting', $steps[0]->process_name);
        $this->assertEquals('Dept M1', $steps[1]->process_name);

        // Verifikasi pencatatan log
        $this->assertDatabaseHas('work_request_logs', [
            'work_request_id' => $wr->id,
            'action_type' => 'routing_update',
        ]);
    }

    public function test_routing_update_is_forbidden_if_work_request_already_processed(): void
    {
        $newComponent = Component::create([
            'name' => 'Part Ad-Hoc 2',
            'part_number' => 'NEW-20261120-XYZ9',
            'status' => 'pending_catalog',
        ]);

        // Tiket sudah mulai berproduksi (quantity_produced > 0)
        $wr = WorkRequest::create([
            'wri_number' => 'WR/Gudang/20261120/002/Baru/1',
            'component_id' => $newComponent->id,
            'requested_by_user_id' => $this->operatorPengaju->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'quantity_requested' => 10,
            'quantity_produced' => 4,
            'status' => 'Proses: Dept M1 (In)',
            'is_final' => false,
        ]);

        $this->assertFalse($wr->canUserEditRouting($this->operatorPengaju));

        $response = $this->actingAs($this->operatorPengaju)->put(route('work-requests.update-routing', $wr), [
            'target_departments' => [
                ['department_name' => 'Dept Laser Cutting'],
            ],
            'reason' => 'Perubahan alur pengerjaan',
        ]);

        $response->assertSessionHas('error');

        // Pastikan alur TIDAK berubah
        $wr->refresh();
        $this->assertEquals('Dept M1', $wr->routing_text);
    }

    public function test_unauthorized_user_cannot_update_routing(): void
    {
        $newComponent = Component::create([
            'name' => 'Part Ad-Hoc 3',
            'part_number' => 'NEW-20261120-KLMN',
            'status' => 'pending_catalog',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WR/Gudang/20261120/003/Baru/1',
            'component_id' => $newComponent->id,
            'requested_by_user_id' => $this->operatorPengaju->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'quantity_requested' => 5,
            'quantity_produced' => 0,
            'status' => 'Dalam antrian',
            'is_final' => false,
        ]);

        // Operator lain dari departemen berbeda yang bukan pengaju / bukan penerima
        $this->assertFalse($wr->canUserEditRouting($this->operatorLain));

        $response = $this->actingAs($this->operatorLain)->put(route('work-requests.update-routing', $wr), [
            'target_departments' => [
                ['department_name' => 'Dept Laser Cutting'],
            ],
            'reason' => 'Coba ubah tanpa wewenang',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_reason_is_required_to_update_routing(): void
    {
        $newComponent = Component::create([
            'name' => 'Part Ad-Hoc 4',
            'part_number' => 'NEW-20261120-REAS',
            'status' => 'pending_catalog',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WR/Gudang/20261120/006/REAS/1',
            'component_id' => $newComponent->id,
            'requested_by_user_id' => $this->operatorPengaju->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'quantity_requested' => 5,
            'quantity_produced' => 0,
            'status' => 'Dalam antrian',
            'is_final' => false,
        ]);

        $response = $this->actingAs($this->operatorPengaju)->put(route('work-requests.update-routing', $wr), [
            'target_departments' => [
                ['department_name' => 'Dept Laser Cutting'],
            ],
            'reason' => '', // Kosong -> harus gagal validasi
        ]);

        $response->assertSessionHasErrors('reason');
    }

    public function test_admin_can_update_routing_even_for_standard_parts_before_processing(): void
    {
        // Komponen standar master katalog
        $standardComponent = Component::create([
            'name' => 'Plat Bawah STD',
            'part_number' => 'STD-001',
            'status' => 'active',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WR/Gudang/20261120/004/STD001/1',
            'component_id' => $standardComponent->id,
            'requested_by_user_id' => $this->operatorPengaju->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'quantity_requested' => 10,
            'quantity_produced' => 0,
            'status' => 'Dalam antrian',
            'is_final' => false,
        ]);

        // Operator biasa tidak boleh edit part standar
        $this->assertFalse($wr->canUserEditRouting($this->operatorPengaju));
        // Admin Gudang boleh edit selagi belum diproses
        $this->assertTrue($wr->canUserEditRouting($this->admin));

        $response = $this->actingAs($this->admin)->put(route('work-requests.update-routing', $wr), [
            'target_departments' => [
                ['department_name' => 'Dept Laser Cutting'],
            ],
            'reason' => 'Koreksi admin sebelum mesin mulai jalan',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $wr->refresh();
        $this->assertEquals('Dept Laser Cutting', $wr->routing_text);
    }

    public function test_ajax_routing_update_returns_json_response(): void
    {
        $newComponent = Component::create([
            'name' => 'Part Ad-Hoc AJAX',
            'part_number' => 'NEW-20261120-AJAX',
            'status' => 'pending_catalog',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WR/Gudang/20261120/005/AJAX/1',
            'component_id' => $newComponent->id,
            'requested_by_user_id' => $this->operatorPengaju->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'quantity_requested' => 10,
            'quantity_produced' => 0,
            'status' => 'Dalam antrian',
            'is_final' => false,
        ]);

        $response = $this->actingAs($this->operatorPengaju)
            ->json('PUT', route('work-requests.update-routing', $wr), [
                'target_departments' => [
                    ['department_name' => 'Dept Laser Cutting'],
                ],
                'reason' => 'Perubahan alur via AJAX',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'wri_number' => $wr->wri_number,
            'routing_text' => 'Dept Laser Cutting',
            'dept_penerima' => 'Dept Laser Cutting',
        ]);
    }
}
