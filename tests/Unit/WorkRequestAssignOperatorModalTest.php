<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkRequest;
use App\Models\WorkRequestLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkRequestAssignOperatorModalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Build clean SQLite in-memory tables
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('alias')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('department_id')->nullable();
            $table->string('department')->nullable();
            $table->string('role')->default('operator');
            $table->string('work_area')->nullable();
            $table->boolean('can_access_all_departments')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

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

        Schema::create('work_requests', function (Blueprint $table) {
            $table->id();
            $table->string('wri_number')->unique();
            $table->foreignId('component_id')->nullable();
            $table->foreignId('requested_by')->nullable();
            $table->string('dept_pengaju')->nullable();
            $table->string('dept_penerima')->nullable();
            $table->decimal('quantity_requested', 12, 2)->default(1);
            $table->string('approval_pengaju')->default('Menunggu');
            $table->string('approval_penerima')->default('Menunggu');
            $table->string('assigned_operator_name')->nullable();
            $table->string('status')->default('Dalam antrian');
            $table->string('current_process_status')->default('Dalam antrian');
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });

        Schema::create('work_request_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_request_id');
            $table->integer('step_number');
            $table->string('operator_name')->nullable();
            $table->timestamps();
        });

        Schema::create('work_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_request_id');
            $table->string('action_type');
            $table->string('status_label');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('quantity_accumulated', 12, 2)->default(0);
            $table->string('operator_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('module')->nullable();
            $table->string('action')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_role')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Test department operators mapping correctly groups operators by department and alias.
     */
    public function test_department_operators_mapping(): void
    {
        $deptM1 = Department::create(['code' => '2', 'name' => 'Dept M1', 'alias' => 'Machining M1', 'is_active' => true]);
        $deptAks = Department::create(['code' => '6', 'name' => 'Dept Aksesoris', 'alias' => 'Aksesoris Fabrikasi', 'is_active' => true]);

        $opFajar = User::create([
            'name' => 'Pak Fajar',
            'email' => 'fajar@example.com',
            'password' => bcrypt('secret'),
            'department_id' => $deptM1->id,
            'department' => 'Dept M1',
            'is_active' => true
        ]);

        $opDedi = User::create([
            'name' => 'Dedi Pratama',
            'email' => 'dedi@example.com',
            'password' => bcrypt('secret'),
            'department_id' => $deptM1->id,
            'department' => 'Dept M1',
            'is_active' => true
        ]);

        $opJoni = User::create([
            'name' => 'Joni Kuswanto',
            'email' => 'joni@example.com',
            'password' => bcrypt('secret'),
            'department_id' => $deptAks->id,
            'department' => 'Dept Aksesoris',
            'is_active' => true
        ]);

        // Build mapping
        $departmentOperatorsMap = [];
        $departments = Department::active()->with(['users' => function ($q) {
            $q->where('is_active', true);
        }])->get();

        foreach ($departments as $dept) {
            $cleanName = preg_replace('/[^a-z0-9]/', '', strtolower($dept->name));
            $userNames = $dept->users->pluck('name')->unique()->values()->all();
            if ($cleanName) {
                $departmentOperatorsMap[$cleanName] = $userNames;
            }
            if ($dept->alias) {
                $cleanAlias = preg_replace('/[^a-z0-9]/', '', strtolower($dept->alias));
                if ($cleanAlias) {
                    $departmentOperatorsMap[$cleanAlias] = $userNames;
                }
            }
        }

        $this->assertArrayHasKey('deptm1', $departmentOperatorsMap);
        $this->assertArrayHasKey('machiningm1', $departmentOperatorsMap);
        $this->assertContains('Pak Fajar', $departmentOperatorsMap['deptm1']);
        $this->assertContains('Dedi Pratama', $departmentOperatorsMap['deptm1']);
        $this->assertNotContains('Joni Kuswanto', $departmentOperatorsMap['deptm1']);

        $this->assertArrayHasKey('deptaksesoris', $departmentOperatorsMap);
        $this->assertContains('Joni Kuswanto', $departmentOperatorsMap['deptaksesoris']);
    }

    /**
     * Test approving receiver without notes works and generates default audit notes.
     */
    public function test_approve_penerima_without_notes_works_cleanly(): void
    {
        $deptM1 = Department::create(['code' => '2', 'name' => 'Dept M1', 'is_active' => true]);

        $roleSpv = \Spatie\Permission\Models\Role::create(['name' => 'supervisor']);

        $kaMesin = User::create([
            'name' => 'Ka Mesin Fajar',
            'email' => 'ka.fajar@example.com',
            'password' => bcrypt('secret'),
            'department_id' => $deptM1->id,
            'department' => 'Dept M1',
            'role' => 'supervisor',
            'can_access_all_departments' => false,
            'is_active' => true
        ]);
        $kaMesin->assignRole($roleSpv);

        $this->actingAs($kaMesin);

        $wri = WorkRequest::create([
            'wri_number' => 'WRI/20261111/001',
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept M1',
            'quantity_requested' => 10,
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Menunggu',
            'status' => 'Menunggu Verifikasi',
        ]);

        $controller = new \App\Http\Controllers\WorkRequestController();
        $request = Request::create(route('work-requests.approve-penerima', $wri), 'POST', [
            'operator_name' => 'Dedi Pratama',
            // 'notes' is omitted entirely
        ]);

        $response = $controller->approvePenerima($request, $wri);

        $wri->refresh();
        $this->assertEquals('Diterima', $wri->approval_penerima);
        $this->assertEquals('Dedi Pratama', $wri->assigned_operator_name);
        $this->assertEquals('Dalam antrian', $wri->status);

        // Verify log was created with default note
        $log = WorkRequestLog::where('work_request_id', $wri->id)->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Ka Mesin Fajar', $log->notes);
        $this->assertStringContainsString('Dedi Pratama', $log->notes);
    }

    /**
     * Test assign_operator_modal blade file does not contain notes textarea and has clean markup.
     */
    public function test_assign_operator_modal_blade_markup_is_minimalist(): void
    {
        $path = resource_path('views/work_requests/partials/assign_operator_modal.blade.php');
        $this->assertFileExists($path);

        $content = file_get_contents($path);

        // Ensure textarea is eliminated
        $this->assertStringNotContainsString('<textarea', $content);
        $this->assertStringNotContainsString('Catatan Kesanggupan', $content);

        // Ensure compact width is used
        $this->assertStringContainsString('sm:max-w-md', $content);

        // Ensure combobox dropdown is present and chips section is eliminated
        $this->assertStringContainsString('openDropdown', $content);
        $this->assertStringContainsString('assignData.availableOperators', $content);
        $this->assertStringContainsString('currentUserName', $content);
        $this->assertStringNotContainsString('Quick Chips', $content);
    }
}
