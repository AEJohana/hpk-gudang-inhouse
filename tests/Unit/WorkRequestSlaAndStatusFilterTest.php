<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WorkRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkRequestSlaAndStatusFilterTest extends TestCase
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
    }

    public function test_overdue_sla_scope_filters_tickets_older_than_72_hours(): void
    {
        // 1. Tiket baru (< 72 jam, belum final)
        $newWr = WorkRequest::create([
            'wri_number' => 'WRI-NEW-01',
            'status' => 'Proses: Laser (In)',
            'is_final' => false,
        ]);
        $newWr->created_at = now()->subHours(24);
        $newWr->save();

        // 2. Tiket lama (> 72 jam / 4 hari lalu, belum final) -> OVERDUE SLA
        $overdueWr = WorkRequest::create([
            'wri_number' => 'WRI-LATE-01',
            'status' => 'Proses: Laser (In)',
            'is_final' => false,
        ]);
        $overdueWr->created_at = now()->subDays(4);
        $overdueWr->save();

        // 3. Tiket lama tapi sudah final / sudah diambil -> TIDAK OVERDUE
        $finishedWr = WorkRequest::create([
            'wri_number' => 'WRI-DONE-01',
            'status' => 'Sudah Diambil',
            'is_final' => true,
        ]);
        $finishedWr->created_at = now()->subDays(5);
        $finishedWr->save();

        $overdueList = WorkRequest::overdueSla()->pluck('wri_number')->toArray();

        $this->assertContains('WRI-LATE-01', $overdueList);
        $this->assertNotContains('WRI-NEW-01', $overdueList);
        $this->assertNotContains('WRI-DONE-01', $overdueList);
    }

    public function test_status_filter_categories(): void
    {
        // Tiket 1: Perlu Verifikasi
        $wrVerif = WorkRequest::create([
            'wri_number' => 'WRI-VERIF',
            'status' => 'Dalam antrian',
            'approval_pengaju' => 'Menunggu',
            'approval_penerima' => 'Menunggu',
            'is_final' => false,
        ]);

        // Tiket 2: Sedang Proses (Mesin)
        $wrProses = WorkRequest::create([
            'wri_number' => 'WRI-PROSES',
            'status' => 'Proses: Laser (In)',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => false,
        ]);

        // Tiket 3: Sedang Proses (MH Antar Mesin)
        $wrMhTransit = WorkRequest::create([
            'wri_number' => 'WRI-MH-TRANSIT',
            'status' => 'MH Pickup: Laser',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => false,
        ]);

        // Tiket 4: Siap Ambil (Siap Diambil / Menuju Pengaju)
        $wrSiap = WorkRequest::create([
            'wri_number' => 'WRI-SIAP',
            'status' => 'Siap Diambil',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => false,
        ]);

        $wrMenuju = WorkRequest::create([
            'wri_number' => 'WRI-MENUJU',
            'status' => 'Menuju Pengaju',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => false,
        ]);

        // Tiket 5: Selesai
        $wrSelesai = WorkRequest::create([
            'wri_number' => 'WRI-SELESAI',
            'status' => 'Sudah Diambil',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => true,
        ]);

        // Test Filter Verifikasi
        $verifList = WorkRequest::needsApproval()->pluck('wri_number')->toArray();
        $this->assertContains('WRI-VERIF', $verifList);
        $this->assertNotContains('WRI-PROSES', $verifList);

        // Test Filter Sedang Proses
        $prosesList = WorkRequest::where(function ($q) {
            $q->where('status', 'like', '%antrian%')
              ->orWhere('status', 'submitted')
              ->orWhere('status', 'like', '%Proses:%')
              ->orWhere('status', 'in_production')
              ->orWhere('status', 'like', '%MH Pickup%');
        })->where('is_final', false)
          ->where('status', 'not like', '%Siap Diambil%')
          ->where('status', 'not like', '%Menuju Pengaju%')
          ->pluck('wri_number')->toArray();

        $this->assertContains('WRI-PROSES', $prosesList);
        $this->assertContains('WRI-MH-TRANSIT', $prosesList);
        $this->assertNotContains('WRI-SIAP', $prosesList);
        $this->assertNotContains('WRI-SELESAI', $prosesList);

        // Test Filter Siap Ambil (Menuju Pengaju)
        $siapList = WorkRequest::where(function ($q) {
            $q->where('status', 'like', '%Siap Diambil%')
              ->orWhere('status', 'like', '%Menuju Pengaju%')
              ->orWhere('status', 'ready_for_warehouse');
        })->where('is_final', false)->pluck('wri_number')->toArray();

        $this->assertContains('WRI-SIAP', $siapList);
        $this->assertContains('WRI-MENUJU', $siapList);
        $this->assertNotContains('WRI-PROSES', $siapList);
        $this->assertNotContains('WRI-SELESAI', $siapList);

        // Test Filter Selesai
        $selesaiList = WorkRequest::where(function ($q) {
            $q->where('is_final', true)
              ->orWhere(function ($sub) {
                  $sub->where(function ($w) {
                      $w->where('status', 'like', '%Sudah Diambil%')
                        ->orWhere('status', 'received');
                  })->where('status', 'not like', '%Parsial%');
              });
        })->pluck('wri_number')->toArray();

        $this->assertContains('WRI-SELESAI', $selesaiList);
        $this->assertNotContains('WRI-SIAP', $selesaiList);
        $this->assertNotContains('WRI-PROSES', $selesaiList);
    }
}
