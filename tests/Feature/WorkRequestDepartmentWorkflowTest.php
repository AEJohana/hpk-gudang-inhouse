<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Department;
use App\Models\Location;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkRequest;
use App\Models\WorkRequestStep;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkRequestDepartmentWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $operatorGudang;
    protected User $spvGudang;
    protected User $operatorM1;
    protected User $spvM1;
    protected User $operatorBandSaw;
    protected User $operatorMH;
    protected Component $component;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles & permissions
        $roleAdmin = Role::firstOrCreate(['name' => 'admin_gudang']);
        $roleSpv = Role::firstOrCreate(['name' => 'supervisor']);
        $roleOp = Role::firstOrCreate(['name' => 'operator']);
        $roleOpMh = Role::firstOrCreate(['name' => 'operator_mh']);

        Permission::firstOrCreate(['name' => 'work-request.view']);
        Permission::firstOrCreate(['name' => 'work-request.create']);
        Permission::firstOrCreate(['name' => 'work-request.approve']);
        Permission::firstOrCreate(['name' => 'work-request.process']);
        Permission::firstOrCreate(['name' => 'material-handling.process']);

        $roleAdmin->givePermissionTo(Permission::all());
        $roleSpv->givePermissionTo(['work-request.view', 'work-request.create', 'work-request.approve', 'work-request.process']);
        $roleOp->givePermissionTo(['work-request.view', 'work-request.process']);
        $roleOpMh->givePermissionTo(['work-request.view', 'work-request.process', 'material-handling.process']);

        $deptGudang = Department::firstOrCreate(['name' => 'Dept Gudang'], ['code' => '11', 'is_active' => true]);
        $deptM1 = Department::firstOrCreate(['name' => 'Dept M1'], ['code' => '2', 'is_active' => true]);
        $deptBandSaw = Department::firstOrCreate(['name' => 'Dept Band Saw'], ['code' => '5', 'is_active' => true]);

        // Users
        $this->admin = User::factory()->create([
            'role' => 'admin_gudang',
            'department_id' => $deptGudang->id,
            'department' => 'Dept Gudang',
            'work_area' => 'semua',
            'can_access_all_departments' => true,
        ]);
        $this->admin->assignRole('admin_gudang');

        $this->operatorGudang = User::factory()->create([
            'role' => 'operator',
            'department_id' => $deptGudang->id,
            'department' => 'Dept Gudang',
            'work_area' => 'gudang',
            'can_access_all_departments' => false,
        ]);
        $this->operatorGudang->assignRole('operator');
        $this->operatorGudang->givePermissionTo(['work-request.view', 'work-request.create']);

        $this->spvGudang = User::factory()->create([
            'role' => 'supervisor',
            'department_id' => $deptGudang->id,
            'department' => 'Dept Gudang',
            'work_area' => 'gudang',
            'can_access_all_departments' => false,
        ]);
        $this->spvGudang->assignRole('supervisor');
        $this->spvGudang->givePermissionTo(['work-request.view', 'work-request.create', 'work-request.approve']);

        $this->spvM1 = User::factory()->create([
            'role' => 'supervisor',
            'department_id' => $deptM1->id,
            'department' => 'Dept M1',
            'work_area' => 'mesin_center',
            'can_access_all_departments' => false,
        ]);
        $this->spvM1->assignRole('supervisor');
        $this->spvM1->givePermissionTo(['work-request.view', 'work-request.approve', 'work-request.process']);

        $this->operatorM1 = User::factory()->create([
            'name' => 'Fajar M1',
            'role' => 'operator',
            'department_id' => $deptM1->id,
            'department' => 'Dept M1',
            'work_area' => 'mesin_center',
            'can_access_all_departments' => false,
        ]);
        $this->operatorM1->assignRole('operator');
        $this->operatorM1->givePermissionTo(['work-request.view', 'work-request.process']);

        $this->operatorBandSaw = User::factory()->create([
            'role' => 'operator',
            'department_id' => $deptBandSaw->id,
            'department' => 'Dept Band Saw',
            'work_area' => 'mesin_center',
            'can_access_all_departments' => false,
        ]);
        $this->operatorBandSaw->assignRole('operator');
        $this->operatorBandSaw->givePermissionTo(['work-request.view']);

        $this->operatorMH = User::factory()->create([
            'role' => 'operator_mh',
            'department_id' => $deptGudang->id,
            'department' => 'Dept Gudang',
            'work_area' => 'material_handling',
            'can_access_all_departments' => false,
        ]);
        $this->operatorMH->assignRole('operator_mh');
        $this->operatorMH->givePermissionTo(['work-request.view', 'work-request.process', 'material-handling.process']);

        // Component & Location
        $warehouse = Warehouse::firstOrCreate(['code' => 'W01'], ['name' => 'Gudang Utama']);
        $this->location = Location::firstOrCreate([
            'warehouse_id' => $warehouse->id,
            'rack_number' => 'R1',
        ], [
            'zone_name' => 'Area Fabrikasi',
            'zone_code' => '1',
        ]);
        $this->component = Component::firstOrCreate([
            'part_number' => 'PLT-SKU-001',
        ], [
            'name' => 'Plat Siku Bawah',
            'category' => 'Raw Material',
            'uom' => 'PCS',
            'is_active' => true,
        ]);
    }

    public function test_receiving_dept_cannot_see_wr_before_pengaju_approval()
    {
        // 1. Operator Gudang creates WR to Dept M1
        $wri = WorkRequest::create([
            'wri_number' => 'WRI/DeptGudang/TEST01',
            'component_id' => $this->component->id,
            'requested_by_user_id' => $this->operatorGudang->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'approval_pengaju' => 'Menunggu',
            'approval_penerima' => 'Menunggu',
            'quantity_requested' => 100,
            'status' => 'Dalam antrian',
        ]);

        // Gudang sees it
        $gudangList = WorkRequest::forUser($this->operatorGudang)->pluck('id')->toArray();
        $this->assertContains($wri->id, $gudangList);

        // Dept M1 CANNOT see it yet! (BR-06)
        $m1List = WorkRequest::forUser($this->spvM1)->pluck('id')->toArray();
        $this->assertNotContains($wri->id, $m1List);

        // Dept Band Saw CANNOT see it either
        $bandSawList = WorkRequest::forUser($this->operatorBandSaw)->pluck('id')->toArray();
        $this->assertNotContains($wri->id, $bandSawList);
    }

    public function test_receiving_dept_sees_wr_after_pengaju_approval()
    {
        $wri = WorkRequest::create([
            'wri_number' => 'WRI/DeptGudang/TEST02',
            'component_id' => $this->component->id,
            'requested_by_user_id' => $this->operatorGudang->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'approval_pengaju' => 'Menunggu',
            'approval_penerima' => 'Menunggu',
            'quantity_requested' => 100,
            'status' => 'Dalam antrian',
        ]);

        // 2. Ka. Gudang approves
        $response = $this->actingAs($this->spvGudang)
            ->post(route('work-requests.approve-pengaju', $wri));
        $response->assertRedirect();

        $wri->refresh();
        $this->assertEquals('Diterima', $wri->approval_pengaju);

        // Now Dept M1 CAN see it!
        $m1List = WorkRequest::forUser($this->spvM1)->pluck('id')->toArray();
        $this->assertContains($wri->id, $m1List);

        // Dept Band Saw STILL cannot see it
        $bandSawList = WorkRequest::forUser($this->operatorBandSaw)->pluck('id')->toArray();
        $this->assertNotContains($wri->id, $bandSawList);
    }

    public function test_receiving_head_approves_and_assigns_operator_name()
    {
        $wri = WorkRequest::create([
            'wri_number' => 'WRI/DeptGudang/TEST03',
            'component_id' => $this->component->id,
            'requested_by_user_id' => $this->operatorGudang->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Menunggu',
            'quantity_requested' => 100,
            'status' => 'Dalam antrian',
        ]);

        WorkRequestStep::create([
            'work_request_id' => $wri->id,
            'step_number' => 1,
            'machine_id' => 1,
            'process_name' => 'Dept M1',
            'status' => 'pending',
        ]);

        // Ka. M1 approves & types operator name 'Fajar M1'
        $response = $this->actingAs($this->spvM1)
            ->post(route('work-requests.approve-penerima', $wri), [
                'operator_name' => 'Fajar M1',
                'notes' => 'Mesin siap dikerjakan shift 1',
            ]);
        $response->assertRedirect();

        $wri->refresh();
        $this->assertEquals('Diterima', $wri->approval_penerima);
        $this->assertEquals('Fajar M1', $wri->assigned_operator_name);
        $this->assertEquals('Dalam antrian', $wri->status);

        $step1 = $wri->steps()->where('step_number', 1)->first();
        $this->assertEquals('Fajar M1', $step1->operator_name);
    }

    public function test_process_in_locks_quantity_and_forbids_defect()
    {
        $wri = WorkRequest::create([
            'wri_number' => 'WRI/DeptGudang/TEST04',
            'component_id' => $this->component->id,
            'requested_by_user_id' => $this->operatorGudang->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'assigned_operator_name' => 'Fajar M1',
            'quantity_requested' => 100,
            'status' => 'Dalam antrian',
        ]);

        // Operator M1 starts: Proses: Dept M1 (In)
        $response = $this->actingAs($this->operatorM1)
            ->post(route('work-requests.process-action', $wri), [
                'action_type' => 'process',
                'target_status' => 'Proses: Dept M1 (In)',
                'process_qty' => 50, // Should be forced to full 100 because (In) locks full remaining
                'operator_name' => 'Fajar M1',
            ]);
        $response->assertRedirect();

        $wri->refresh();
        $this->assertEquals('Proses: Dept M1 (In)', $wri->status);
        $this->assertEquals(0, (float) $wri->quantity_defect);
    }

    public function test_process_out_calculates_partial_and_deducts_defect()
    {
        $wri = WorkRequest::create([
            'wri_number' => 'WRI/DeptGudang/TEST05',
            'component_id' => $this->component->id,
            'requested_by_user_id' => $this->operatorGudang->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'assigned_operator_name' => 'Fajar M1',
            'quantity_requested' => 100,
            'status' => 'Proses: Dept M1 (In)',
        ]);

        // Operator M1 finishes out: 75 pcs finished, 5 defect (scrap)
        $response = $this->actingAs($this->operatorM1)
            ->post(route('work-requests.process-action', $wri), [
                'action_type' => 'process',
                'target_status' => 'Proses: Dept M1 (Out)',
                'process_qty' => 75,
                'defect_qty' => 5,
                'defect_reason' => 'Salah potong plat',
                'operator_name' => 'Fajar M1',
            ]);
        $response->assertRedirect();

        $wri->refresh();
        // 100 requested - 5 defect = 95 requested
        $this->assertEquals(95, (float) $wri->quantity_requested);
        $this->assertEquals(5, (float) $wri->quantity_defect);
        // 75 < 95 => Parsial
        $this->assertStringContainsString('Parsial', $wri->status);
        $this->assertEquals(75, (float) $wri->current_batch_qty);
    }

    public function test_only_material_handling_can_pickup()
    {
        $wri = WorkRequest::create([
            'wri_number' => 'WRI/DeptGudang/TEST06',
            'component_id' => $this->component->id,
            'requested_by_user_id' => $this->operatorGudang->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'quantity_requested' => 95,
            'current_batch_qty' => 75,
            'status' => 'Parsial > Siap Diambil',
        ]);

        // 1. Operator Mesin tries to pickup -> FORBIDDEN
        $failResponse = $this->actingAs($this->operatorM1)
            ->post(route('work-requests.process-action', $wri), [
                'action_type' => 'process',
                'target_status' => 'Menuju Pengaju',
                'process_qty' => 75,
            ]);
        $failResponse->assertSessionHas('error');

        // 2. Operator MH executes pickup -> SUCCEEDS!
        $successResponse = $this->actingAs($this->operatorMH)
            ->post(route('work-requests.process-action', $wri), [
                'action_type' => 'process',
                'target_status' => 'Menuju Pengaju',
                'process_qty' => 75,
            ]);
        $successResponse->assertSessionHas('success');

        $wri->refresh();
        $this->assertStringContainsString('Menuju Pengaju', $wri->status);
    }

    public function test_only_gudang_can_receive_and_increments_stock()
    {
        $wri = WorkRequest::create([
            'wri_number' => 'WRI/DeptGudang/TEST07',
            'component_id' => $this->component->id,
            'requested_by_user_id' => $this->operatorGudang->id,
            'target_location_id' => $this->location->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'routing_text' => 'Dept M1',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'quantity_requested' => 75,
            'quantity_received' => 0,
            'current_batch_qty' => 75,
            'status' => 'Menuju Pengaju',
        ]);

        $initialStock = StockBalance::where('component_id', $this->component->id)->sum('quantity');

        // 1. Operator M1 tries to receive -> FORBIDDEN
        $failResponse = $this->actingAs($this->operatorM1)
            ->post(route('work-requests.process-action', $wri), [
                'action_type' => 'process',
                'target_status' => 'Sudah Diambil',
                'process_qty' => 75,
            ]);
        $failResponse->assertSessionHas('error');

        // 2. Gudang user executes receive -> SUCCEEDS & STOCKS INCREMENT!
        $successResponse = $this->actingAs($this->operatorGudang)
            ->post(route('work-requests.process-action', $wri), [
                'action_type' => 'process',
                'target_status' => 'Sudah Diambil',
                'process_qty' => 75,
            ]);
        $successResponse->assertSessionHas('success');

        $wri->refresh();
        $this->assertStringContainsString('Sudah Diambil', $wri->status);
        $this->assertTrue((bool) $wri->is_final);
        $this->assertEquals(75, (float) $wri->quantity_received);

        $newStock = StockBalance::where('component_id', $this->component->id)->sum('quantity');
        $this->assertEquals($initialStock + 75, $newStock);
    }
}
