<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Department;
use App\Models\Location;
use App\Models\QrRequest;
use App\Models\StockBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LiveFilterFragmentTest extends TestCase
{
    protected User $admin;

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

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('alias')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('work_area')->nullable();
            $table->string('department')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->boolean('can_access_all_departments')->default(false);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->string('zone_code')->nullable();
            $table->string('zone_name')->nullable();
            $table->string('rack_number')->nullable();
            $table->string('rack_code')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('part_number')->nullable();
            $table->string('uom')->default('PCS');
            $table->string('category')->nullable();
            $table->string('specification')->nullable();
            $table->string('length_mm')->nullable();
            $table->string('width_or_diameter_mm')->nullable();
            $table->string('thickness_or_height_mm')->nullable();
            $table->decimal('minimum_stock', 12, 2)->default(0);
            $table->unsignedBigInteger('default_location_id')->nullable();
            $table->string('default_shelf_level')->nullable();
            $table->string('default_slot_number')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('source')->default('master');
            $table->timestamps();
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('component_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number');
            $table->string('type');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('work_request_id')->nullable();
            $table->string('spk_number')->nullable();
            $table->string('reference_document')->nullable();
            $table->date('transaction_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('component_id');
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('qr_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number');
            $table->unsignedBigInteger('component_id');
            $table->unsignedBigInteger('requested_by');
            $table->string('label_type')->default('item');
            $table->integer('print_qty')->default(1);
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('work_requests', function (Blueprint $table) {
            $table->id();
            $table->string('wri_number');
            $table->unsignedBigInteger('component_id')->nullable();
            $table->unsignedBigInteger('requested_by_user_id')->nullable();
            $table->string('dept_pengaju')->default('Dept Gudang');
            $table->string('dept_penerima')->default('Dept Laser Cutting');
            $table->string('routing_text')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('Dalam antrian');
            $table->decimal('quantity_requested', 12, 2)->default(10);
            $table->decimal('quantity_produced', 12, 2)->default(0);
            $table->decimal('quantity_received', 12, 2)->default(0);
            $table->decimal('quantity_defect', 12, 2)->default(0);
            $table->string('approval_pengaju')->default('Diterima');
            $table->string('approval_penerima')->default('Diterima');
            $table->string('assigned_operator_name')->nullable();
            $table->string('spk_reference')->nullable();
            $table->unsignedBigInteger('target_location_id')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });

        Schema::create('work_request_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_request_id');
            $table->integer('step_number')->default(1);
            $table->string('process_name');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('work_request_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_request_id');
            $table->string('action_type', 50);
            $table->string('status_label', 150)->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('operator_name')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });

        $role = Role::create(['name' => 'admin_gudang', 'guard_name' => 'web']);
        foreach ([
            'master-komponen.view',
            'transaksi-komponen.view',
            'label-qr.view',
            'work-request.view',
            'work-request.safety-stock',
            'work-request.print',
            'audit-log.view',
        ] as $permName) {
            $perm = Permission::create(['name' => $permName, 'guard_name' => 'web']);
            $role->givePermissionTo($perm);
        }

        $dept = Department::create(['code' => '11', 'name' => 'Dept Gudang']);
        $this->admin = User::create([
            'name' => 'Admin Live Filter',
            'username' => 'admin_live',
            'password' => bcrypt('password'),
            'department_id' => $dept->id,
            'department' => $dept->name,
            'work_area' => 'gudang',
            'can_access_all_departments' => true,
            'is_active' => true,
        ]);
        $this->admin->assignRole($role);
    }

    public function test_live_fragment_responses_return_filtered_results_without_full_document_shell(): void
    {
        $this->withoutExceptionHandling();
        $loc = Location::create(['zone_code' => '1', 'rack_code' => 'R1', 'code' => '1-R1']);

        $compMatch = Component::create([
            'part_number' => 'LIVE-MATCH-01',
            'name' => 'BRACKET KHUSUS LIVE',
            'category' => 'subframe',
            'minimum_stock' => 20,
            'default_location_id' => $loc->id,
            'source' => 'master',
        ]);
        $compOther = Component::create([
            'part_number' => 'OTHER-PART-99',
            'name' => 'PIN STANDAR',
            'category' => 'assy',
            'minimum_stock' => 20,
            'default_location_id' => $loc->id,
            'source' => 'master',
        ]);
        StockBalance::create(['component_id' => $compMatch->id, 'location_id' => $loc->id, 'quantity' => 5]);
        StockBalance::create(['component_id' => $compOther->id, 'location_id' => $loc->id, 'quantity' => 5]);

        Transaction::create([
            'transaction_number' => 'TRX-LIVE-001',
            'type' => 'inbound',
            'user_id' => $this->admin->id,
            'transaction_date' => now(),
        ]);
        Transaction::create([
            'transaction_number' => 'TRX-OTHER-002',
            'type' => 'outbound',
            'user_id' => $this->admin->id,
            'transaction_date' => now(),
        ]);

        QrRequest::create([
            'request_number' => 'QR-LIVE-001',
            'component_id' => $compMatch->id,
            'requested_by' => $this->admin->id,
            'status' => 'pending',
        ]);
        QrRequest::create([
            'request_number' => 'QR-OTHER-002',
            'component_id' => $compOther->id,
            'requested_by' => $this->admin->id,
            'status' => 'pending',
        ]);

        User::create([
            'name' => 'Budi Operator Live',
            'username' => 'budi_op_live',
            'password' => bcrypt('password'),
            'work_area' => 'mesin_center',
            'is_active' => true,
        ]);

        // 1. Master Components fragment
        $resComp = $this->actingAs($this->admin)->get(route('components.index', ['_live' => 1, 'search' => 'LIVE-MATCH-01']));
        $resComp->assertStatus(200);
        $resComp->assertDontSee('<!DOCTYPE html>', false);
        $resComp->assertSee('LIVE-MATCH-01');
        $resComp->assertDontSee('OTHER-PART-99');

        // 2. Transactions fragment
        $resTx = $this->actingAs($this->admin)->get(route('transactions.index', ['_live' => 1, 'type' => 'inbound']));
        $resTx->assertStatus(200);
        $resTx->assertDontSee('<!DOCTYPE html>', false);
        $resTx->assertSee('TRX-LIVE-001');
        $resTx->assertDontSee('TRX-OTHER-002');

        // 3. QR Requests fragment
        $resQr = $this->actingAs($this->admin)->get(route('qr-requests.index', ['_live' => 1, 'q' => 'QR-LIVE-001']));
        $resQr->assertStatus(200);
        $resQr->assertDontSee('<!DOCTYPE html>', false);
        $resQr->assertSee('QR-LIVE-001');
        $resQr->assertDontSee('QR-OTHER-002');

        // 4. Safety Stock fragment
        $resSafety = $this->actingAs($this->admin)->get(route('work-requests.safety-stock', ['_live' => 1, 'search' => 'BRACKET KHUSUS']));
        $resSafety->assertStatus(200);
        $resSafety->assertDontSee('<!DOCTYPE html>', false);
        $resSafety->assertSee('BRACKET KHUSUS LIVE');
        $resSafety->assertDontSee('PIN STANDAR');

        // 5. Admin Users fragment
        $resUsers = $this->actingAs($this->admin)->get(route('admin.users.index', ['_live' => 1, 'search' => 'budi_op_live']));
        $resUsers->assertStatus(200);
        $resUsers->assertDontSee('<!DOCTYPE html>', false);
        $resUsers->assertSee('Budi Operator Live');
        $resUsers->assertDontSee('Admin Live Filter');

        // 6. Admin Audit Logs fragment
        $resLogs = $this->actingAs($this->admin)->get(route('admin.audit-logs.index', ['_live' => 1, 'period' => 'today']));
        $resLogs->assertStatus(200);
        $resLogs->assertDontSee('<!DOCTYPE html>', false);

        // 7. Work Requests partial & Print Queue fragment
        \App\Models\WorkRequest::create([
            'wri_number' => 'WR/Dept Gudang/20261126/1/LIVE/1',
            'component_id' => $compMatch->id,
            'requested_by_user_id' => $this->admin->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept Laser Cutting',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'assigned_operator_name' => 'Op Live',
            'status' => 'Dalam antrian',
        ]);
        \App\Models\WorkRequest::create([
            'wri_number' => 'WR/Dept Gudang/20261126/2/OTHER/1',
            'component_id' => $compOther->id,
            'requested_by_user_id' => $this->admin->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept Laser Cutting',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'assigned_operator_name' => 'Op Other',
            'status' => 'Dalam antrian',
        ]);

        $resWri = $this->actingAs($this->admin)->get(route('work-requests.index', ['_partial' => 1, 'search' => 'BRACKET KHUSUS']));
        $resWri->assertStatus(200);
        $resWri->assertDontSee('<!DOCTYPE html>', false);
        $resWri->assertSee('BRACKET KHUSUS LIVE');
        $resWri->assertDontSee('PIN STANDAR');

        $resPrint = $this->actingAs($this->admin)->get(route('work-requests.print-queue', ['_live' => 1, 'search' => 'BRACKET KHUSUS']));
        $resPrint->assertStatus(200);
        $resPrint->assertDontSee('<!DOCTYPE html>', false);
        $resPrint->assertSee('BRACKET KHUSUS LIVE');
        $resPrint->assertDontSee('PIN STANDAR');
    }

    public function test_per_page_whitelist_and_top_bottom_pagination_bars(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            Component::create([
                'part_number' => sprintf('PAG-%03d', $i),
                'name' => sprintf('KOMPONEN PAGINATION %03d', $i),
                'uom' => 'PCS',
                'minimum_stock' => 5,
                'source' => 'master',
            ]);
        }

        // Valid whitelisted per_page=10 triggers 2 pages -> both top (with select) and bottom (without select) bars appear
        $res10 = $this->actingAs($this->admin)->get(route('components.index', ['_live' => 1, 'per_page' => 10]));
        $res10->assertStatus(200);
        $content10 = $res10->getContent();
        $this->assertSame(2, substr_count($content10, 'aria-label="Pagination Navigation"'));
        $this->assertStringContainsString('<option value="10" selected>10 / halaman</option>', $content10);

        // Out-of-whitelist per_page=99999 falls back safely to controller default (15) -> 16 items still span 2 pages
        $resAbuse = $this->actingAs($this->admin)->get(route('components.index', ['_live' => 1, 'per_page' => 99999]));
        $resAbuse->assertStatus(200);
        $contentAbuse = $resAbuse->getContent();
        $this->assertStringContainsString('<option value="15" selected>15 / halaman</option>', $contentAbuse);
        $this->assertSame(2, substr_count($contentAbuse, 'aria-label="Pagination Navigation"'));
    }
}
