<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Department;
use App\Models\Location;
use App\Models\Machine;
use App\Models\User;
use App\Models\WorkRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkRequestAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $spv;
    protected User $operator;
    protected User $restrictedUser;
    protected Department $deptGudang;
    protected Department $deptLaser;
    protected Component $component;
    protected Machine $machine;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->deptGudang = Department::where('code', '11')->first() ?? Department::first();
        $this->deptLaser = Department::where('code', '1')->first() ?? Department::skip(1)->first();

        // 1. Admin Gudang (Global Access)
        $this->admin = User::where('email', 'admin@hydraxle.com')->first();
        $this->admin->department_id = $this->deptGudang->id;
        $this->admin->can_access_all_departments = true;
        $this->admin->save();

        // 2. Supervisor / Ka. Bagian
        $this->spv = User::where('email', 'spv.gudang@hydraxle.com')->first();
        $this->spv->department_id = $this->deptGudang->id;
        $this->spv->can_access_all_departments = false;
        $this->spv->save();

        // 3. Operator
        $this->operator = User::where('email', 'operator@hydraxle.com')->first();
        $this->operator->department_id = $this->deptGudang->id;
        $this->operator->can_access_all_departments = false;
        $this->operator->save();

        // 4. Restricted user without WRI view permission
        $this->restrictedUser = User::factory()->create([
            'department_id' => $this->deptLaser->id,
            'can_access_all_departments' => false,
        ]);
        $guestRole = Role::firstOrCreate(['name' => 'guest_role', 'guard_name' => 'web']);
        $guestRole->syncPermissions(['label-qr.view']);
        $this->restrictedUser->syncRoles([$guestRole]);

        $this->component = Component::first();
        $this->machine = Machine::first();
        $this->location = Location::first();
    }

    public function test_user_without_wri_view_permission_is_forbidden(): void
    {
        $response = $this->actingAs($this->restrictedUser)->get('/work-requests');
        $response->assertStatus(403);
    }

    public function test_operator_creates_wri_with_department_auto_locked_and_pending_approval(): void
    {
        $response = $this->actingAs($this->operator)->post('/work-requests', [
            'component_id' => $this->component->id,
            'quantity_requested' => 10,
            'dept_pengaju' => 'Fake Department Injection', // Non-global user cannot spoof department
            'priority' => 'normal',
            'steps' => [
                [
                    'machine_id' => $this->machine->id,
                    'process_name' => 'Potong Laser Dasar',
                ],
            ],
        ]);

        $response->assertStatus(302);

        $wri = WorkRequest::latest('id')->first();
        $this->assertNotNull($wri);
        // Department must be locked to operator's department
        $this->assertEquals($this->deptGudang->name, $wri->dept_pengaju);
        // Operator request requires approval
        $this->assertEquals('Menunggu', $wri->approval_pengaju);
        $this->assertEquals('Dalam antrian', $wri->status);
    }

    public function test_operator_can_create_wr_with_multi_target_departments(): void
    {
        $response = $this->actingAs($this->operator)->post('/work-requests', [
            'component_id' => $this->component->id,
            'quantity_requested' => 15,
            'priority' => 'high',
            'target_departments' => [
                ['department_name' => 'Laser Cutting', 'notes' => 'Potong plat 8mm'],
                ['department_name' => 'Press Bending', 'notes' => 'Tekuk 90 derajat'],
                ['department_name' => 'Uji 1', 'notes' => 'Cek dimensi'],
            ],
            'notes' => 'Harap diproses sebelum shift 2',
        ]);

        $response->assertStatus(302);

        $wri = WorkRequest::latest('id')->first();
        $this->assertNotNull($wri);
        $this->assertEquals($this->deptGudang->name, $wri->dept_pengaju);
        $this->assertEquals('Dept Laser Cutting -> Dept Press Bending -> Dept Uji 1', $wri->routing_text);
        $this->assertEquals(3, $wri->steps()->count());
        $this->assertNull($wri->spk_reference);
    }

    public function test_ka_bagian_can_approve_operator_wri(): void
    {
        // Operator creates WRI
        $wri = WorkRequest::create([
            'wri_number' => 'WRI/GUDANG/20261014/99/TEST',
            'component_id' => $this->component->id,
            'requested_by_user_id' => $this->operator->id,
            'dept_pengaju' => $this->deptGudang->name,
            'dept_penerima' => 'Machine Shop',
            'routing_text' => 'Potong Laser',
            'approval_pengaju' => 'Menunggu',
            'approval_penerima' => 'Diterima',
            'quantity_requested' => 10,
            'priority' => 'normal',
            'status' => 'Dalam antrian',
            'current_process_status' => 'Dalam antrian',
        ]);

        // Supervisor approves
        $approveResponse = $this->actingAs($this->spv)->post("/work-requests/{$wri->id}/approve-pengaju");
        $approveResponse->assertStatus(302);

        $wri->refresh();
        $this->assertEquals('Diterima', $wri->approval_pengaju);
    }

    public function test_admin_has_global_access_and_can_filter_all_departments(): void
    {
        $response = $this->actingAs($this->admin)->get('/work-requests?scope=all');
        $response->assertStatus(200);
        $response->assertSee('Semua 15 Departemen');
    }

    public function test_sub_access_safety_stock_permission(): void
    {
        // Admin has permission
        $response = $this->actingAs($this->admin)->get('/work-requests/safety-stock');
        $response->assertStatus(200);
        $response->assertSee('Safety Stock Buffer Alert');

        // Operator Mesin does not have safety-stock permission
        $opMesin = User::factory()->create([
            'department_id' => $this->deptLaser->id,
            'can_access_all_departments' => false,
        ]);
        $opMesin->assignRole('operator_mesin');

        $deniedResponse = $this->actingAs($opMesin)->get('/work-requests/safety-stock');
        $deniedResponse->assertStatus(403);
    }

    public function test_monitoring_url_redirects_to_overdue_wri_queue(): void
    {
        $response = $this->actingAs($this->admin)->get('/work-requests/monitoring');
        $response->assertRedirect(route('work-requests.index', ['overdue_sla' => 1]));
    }

    public function test_print_sheet_kanban_card_renders(): void
    {
        $wri = WorkRequest::create([
            'wri_number' => 'WRI/GUDANG/20261014/100/PRINT',
            'component_id' => $this->component->id,
            'requested_by_user_id' => $this->admin->id,
            'dept_pengaju' => $this->deptGudang->name,
            'dept_penerima' => 'Machine Shop',
            'routing_text' => 'Laser -> Bending',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'quantity_requested' => 50,
            'priority' => 'high',
            'status' => 'in_production',
            'current_process_status' => 'Proses: Laser (In)',
        ]);

        $response = $this->actingAs($this->admin)->get("/work-requests/{$wri->id}/print-sheet");
        $response->assertStatus(200);
        $response->assertSee('FORMULIR WORK REQUEST INHOUSE');
        $response->assertSee('KARTU KANBAN');
        $response->assertSee($wri->wri_number);
    }

    public function test_quick_store_component_api(): void
    {
        $response = $this->actingAs($this->operator)->postJson('/api/components/quick-store', [
            'name' => 'BRACKET PINTU SAMPING ADR-25',
            'length_mm' => '250',
            'width_or_diameter_mm' => '120',
            'thickness_or_height_mm' => '8',
            'specification' => 'Plat Baja SS400',
            'uom' => 'PCS',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertDatabaseHas('components', [
            'name' => 'BRACKET PINTU SAMPING ADR-25',
            'length_mm' => '250',
            'thickness_or_height_mm' => '8',
        ]);
    }

    public function test_create_wr_without_due_date_and_spk_reference(): void
    {
        $response = $this->actingAs($this->operator)->post('/work-requests', [
            'component_id' => $this->component->id,
            'quantity_requested' => 15,
            'priority' => 'normal',
            'target_departments' => [
                ['department_name' => 'Dept Laser Cutting', 'notes' => 'Potong pelat'],
                ['department_name' => 'Dept Bending', 'notes' => 'Tekuk 90 deg'],
            ],
            'notes' => 'Toleransi presisi ±0.5mm',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('work_requests', [
            'component_id' => $this->component->id,
            'quantity_requested' => 15,
            'due_date' => null,
            'spk_reference' => null,
            'routing_text' => 'Dept Laser Cutting -> Dept Bending',
        ]);
    }

    public function test_process_action_auto_increments_stock_on_sudah_diambil(): void
    {
        $wri = WorkRequest::create([
            'wri_number' => 'WRI/GUDANG/20261014/101/STOCKTEST',
            'component_id' => $this->component->id,
            'requested_by_user_id' => $this->admin->id,
            'dept_pengaju' => $this->deptGudang->name,
            'dept_penerima' => 'Dept Laser Cutting',
            'routing_text' => 'Dept Laser Cutting',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'quantity_requested' => 20,
            'quantity_received' => 0,
            'priority' => 'normal',
            'status' => 'Menuju Pengaju',
            'is_final' => false,
        ]);

        $initialStock = $this->component->total_stock;

        $this->withoutExceptionHandling();

        $response = $this->actingAs($this->admin)->post("/work-requests/{$wri->id}/process-action", [
            'action_type' => 'process',
            'target_status' => 'Sudah Diambil',
            'process_qty' => 20,
            'operator_name' => 'Admin Test',
        ]);

        $response->assertRedirect();
        $wri->refresh();
        $this->assertEquals('Sudah Diambil', $wri->status);
        $this->assertTrue($wri->is_final);
        $this->assertEquals(20, $wri->quantity_received);

        // Verify physical stock incremented by 20
        $this->assertEquals($initialStock + 20, $this->component->fresh()->total_stock);
        // Verify inbound transaction was generated
        $this->assertDatabaseHas('transactions', [
            'work_request_id' => $wri->id,
            'type' => 'inbound',
            'status' => 'completed',
        ]);
    }

    /**
     * Test synchronization of default access:
     * Only Gudang users have work-request.create by default, while Mesin Center does not.
     */
    public function test_mesin_center_operator_cannot_create_wri_by_default_while_gudang_operator_can(): void
    {
        // 1. Gudang operator has work-request.create
        $this->operator->givePermissionTo('work-request.create');
        $this->actingAs($this->operator)
            ->get('/work-requests/create')
            ->assertOk();

        // 2. Mesin Center operator has view & process but NOT create
        $mesinUser = User::factory()->create([
            'work_area' => 'mesin_center',
            'department_id' => $this->deptLaser->id,
            'can_access_all_departments' => false,
        ]);
        $mesinUser->syncRoles(['operator']);
        $mesinUser->syncPermissions(['work-request.view', 'work-request.process']);

        $this->actingAs($mesinUser)
            ->get('/work-requests/create')
            ->assertForbidden();

        // 3. Admin can explicitly grant create to Mesin Center user
        $mesinUser->givePermissionTo('work-request.create');
        $this->actingAs($mesinUser)
            ->get('/work-requests/create')
            ->assertOk();
    }
}

