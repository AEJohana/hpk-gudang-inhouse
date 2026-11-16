<?php

namespace Tests\Unit;

use App\Http\Controllers\WorkRequestController;
use App\Models\Component;
use App\Models\Department;
use App\Models\Machine;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkRequest;
use App\Models\WorkRequestLog;
use App\Models\WorkRequestStep;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyWrMigrationTest extends TestCase
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
            $table->decimal('total_stock', 12, 2)->default(0);
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

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('module')->nullable();
            $table->string('action')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->nullable();
            $table->string('type');
            $table->unsignedBigInteger('component_id');
            $table->decimal('quantity', 12, 2);
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
            $table->date('due_date')->nullable();
            $table->string('spk_reference')->nullable();
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
    }

    /**
     * Uji format standar 6 segmen nomor WRI:
     * WR/{Dept Pengaju}/{YYYYMMDD}/{Seq}/{Kode Komponen atau 'Baru'}/{SeqItem}
     */
    public function test_standard_6_segment_wri_number_format_for_registered_component(): void
    {
        $user = User::create([
            'name' => 'Operator Gudang',
            'email' => 'operator.gudang@example.com',
            'password' => bcrypt('secret'),
            'work_area' => 'Gudang',
            'department' => 'Gudang',
        ]);
        $this->actingAs($user);

        $comp = Component::create([
            'name' => 'Flange Adapter 2 Inch',
            'part_number' => 'CFPP10100100031W',
            'total_stock' => 100,
        ]);

        $controller = new WorkRequestController();
        $dateStr = '20260608';
        $wriNumber = $controller->generateWriNumber('Gudang', $comp->part_number, $dateStr, 1);

        $this->assertEquals('WR/Dept Gudang/20260608/1/CFPP10100100031W/1', $wriNumber);

        // Verifikasi regex 6 segmen
        $pattern = '/^WR\/Dept\s+[^\/]+\/\d{8}\/\d+\/[^\/]+\/\d+$/';
        $this->assertMatchesRegularExpression($pattern, $wriNumber);
    }

    /**
     * Uji format standar 6 segmen untuk komponen baru (belum terdaftar di master):
     * Segmen ke-5 WAJIB bernilai literal 'Baru'.
     */
    public function test_standard_6_segment_wri_number_format_for_new_component(): void
    {
        $user = User::create([
            'name' => 'Supervisor PPIC',
            'email' => 'ppic@example.com',
            'password' => bcrypt('secret'),
            'work_area' => 'PPIC',
            'department' => 'PPIC',
        ]);
        $this->actingAs($user);

        $controller = new WorkRequestController();
        $dateStr = '20260611';
        $wriNumber = $controller->generateWriNumber('Gudang', 'Baru', $dateStr, 2);

        $this->assertEquals('WR/Dept Gudang/20260611/2/Baru/1', $wriNumber);

        $segments = explode('/', $wriNumber);
        $this->assertCount(6, $segments);
        $this->assertEquals('WR', $segments[0]);
        $this->assertEquals('Dept Gudang', $segments[1]);
        $this->assertEquals('20260611', $segments[2]);
        $this->assertEquals('2', $segments[3]);
        $this->assertEquals('Baru', $segments[4]);
        $this->assertEquals('1', $segments[5]);
    }

    /**
     * Uji pembuatan tiket baru via bulkStore menghasilkan format 6 segmen dengan 'Baru'.
     */
    public function test_bulk_store_creates_proper_wri_number_with_baru(): void
    {
        $user = User::create([
            'name' => 'Staff Gudang',
            'email' => 'staff@example.com',
            'password' => bcrypt('secret'),
            'work_area' => 'Gudang',
            'department' => 'Gudang',
        ]);
        $this->actingAs($user);

        $newComp = Component::create([
            'name' => 'Plat Siku Custom 4mm',
            'part_number' => 'NEW-20260611-001',
            'total_stock' => 0,
        ]);

        $controller = new WorkRequestController();
        $request = Request::create('/work-requests/bulk-store', 'POST', [
            'cart_data' => [
                [
                    'component_id' => $newComp->id,
                    'quantity' => 5,
                    'department_name' => 'Laser',
                ]
            ]
        ]);

        $response = $controller->bulkStore($request);

        $wr = WorkRequest::first();
        $this->assertNotNull($wr);
        $segments = explode('/', $wr->wri_number);
        $this->assertCount(6, $segments);
        $this->assertEquals('WR', $segments[0]);
        $this->assertEquals('Dept Gudang', $segments[1]);
        $this->assertEquals('Baru', $segments[4]);
        $this->assertEquals(1, $segments[5]);
    }

    /**
     * Uji Zero Stock Disturbance:
     * Migrasi tiket atau data lama tidak boleh mengubah stok atau membuat transaksi keluar/masuk.
     */
    public function test_zero_stock_disturbance_protocol(): void
    {
        $comp = Component::create([
            'name' => 'Gear Shaft Pinion',
            'part_number' => 'GSP-9901',
            'total_stock' => 500,
        ]);

        $initialStock = Component::sum('total_stock');
        $initialTransactions = Transaction::count();

        // Simulasikan pendaftaran Work Request migrasi lama
        WorkRequest::create([
            'wri_number' => 'WR/Dept Gudang/20260608/1/GSP-9901/1',
            'component_id' => $comp->id,
            'quantity_requested' => 20,
            'status' => 'selesai',
            'dept_pengaju' => 'Gudang',
            'dept_penerima' => 'Bubut',
        ]);

        $finalStock = Component::sum('total_stock');
        $finalTransactions = Transaction::count();

        $this->assertEquals($initialStock, $finalStock, 'Stok master tidak boleh berkurang saat migrasi riwayat.');
        $this->assertEquals($initialTransactions, $finalTransactions, 'Tidak boleh ada transaksi mutasi baru yang terbentuk.');
    }

    /**
     * Uji Idempotensi (Zero Duplicate):
     * Tiket yang sudah ada dengan wri_number yang sama tidak akan diduplikasi.
     */
    public function test_zero_duplicate_idempotency(): void
    {
        $comp = Component::create([
            'name' => 'Baut M10',
            'part_number' => 'BM10-001',
            'total_stock' => 100,
        ]);

        $wriNumber = 'WR/Dept Gudang/20260608/1/BM10-001/1';

        WorkRequest::create([
            'wri_number' => $wriNumber,
            'component_id' => $comp->id,
            'quantity_requested' => 10,
            'status' => 'selesai',
        ]);

        $this->assertEquals(1, WorkRequest::where('wri_number', $wriNumber)->count());

        // Jalankan pengecekan eksistensi seperti pada command migrasi
        $alreadyExists = WorkRequest::where('wri_number', $wriNumber)->exists();
        $this->assertTrue($alreadyExists);

        // Jika exists, baris dilewati sehingga count tetap 1
        if (!$alreadyExists) {
            WorkRequest::create([
                'wri_number' => $wriNumber,
                'component_id' => $comp->id,
                'quantity_requested' => 10,
                'status' => 'selesai',
            ]);
        }

        $this->assertEquals(1, WorkRequest::where('wri_number', $wriNumber)->count(), 'Nomor tiket tidak boleh duplikat.');
    }
}
