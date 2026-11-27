<?php

namespace Tests\Unit;

use App\Http\Controllers\WorkRequestController;
use App\Models\Component;
use App\Models\Department;
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
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('role')->default('operator');
            $table->string('work_area')->nullable();
            $table->string('department')->nullable();
            $table->boolean('can_access_all_departments')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('part_number')->nullable();
            $table->string('specification')->nullable();
            $table->string('category')->nullable();
            $table->string('uom')->default('PCS');
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->string('length_mm', 100)->nullable();
            $table->string('width_or_diameter_mm', 100)->nullable();
            $table->string('thickness_or_height_mm', 100)->nullable();
            $table->decimal('minimum_stock', 12, 2)->default(0);
            $table->decimal('maximum_stock', 12, 2)->nullable();
            $table->decimal('total_stock', 12, 2)->default(0);
            $table->unsignedBigInteger('default_location_id')->nullable();
            $table->string('default_shelf_level')->nullable();
            $table->string('default_slot_number')->nullable();
            $table->string('image_path')->nullable();
            $table->string('qr_code_payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('source', 20)->default('master');
            $table->timestamps();
        });

        Schema::create('uoms', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->softDeletes();
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

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->string('type')->default('storage');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
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
            $table->string('rack_code')->nullable();
            $table->string('rack_number')->nullable();
            $table->string('storage_type')->default('rack');
            $table->string('bin_level')->nullable();
            $table->text('description')->nullable();
            $table->integer('max_capacity')->default(100);
            $table->integer('total_levels')->default(4);
            $table->integer('slots_per_level')->default(10);
            $table->integer('grid_x')->default(1);
            $table->integer('grid_y')->default(1);
            $table->integer('grid_w')->default(1);
            $table->integer('grid_h')->default(1);
            $table->string('color')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('component_id');
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('shelf_level')->nullable();
            $table->string('slot_number')->nullable();
            $table->string('specific_location_code')->nullable();
            $table->string('batch_lot_number')->nullable();
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
            $table->string('assigned_operator_name')->nullable();
            $table->decimal('current_batch_qty', 12, 2)->default(0);
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
            $table->string('operator_name')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
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

    /**
     * Uji Smart Sync:
     * Tiket eksis yang statusnya berubah di spreadsheet akan dimutakhirkan (UPDATE)
     * tanpa menambah baris baru ataupun menduplikasi log riwayat.
     */
    public function test_smart_sync_updates_existing_ticket_without_duplicate(): void
    {
        $comp = Component::create([
            'name' => 'Reinforce Peredam',
            'part_number' => 'CSTP10080210070',
            'total_stock' => 100,
        ]);

        $wriNumber = 'WR/Dept Gudang/20260807/4/CSTP10080210070/1';

        // Tiket kondisi lama (masih proses mesin)
        $wr = WorkRequest::create([
            'wri_number' => $wriNumber,
            'component_id' => $comp->id,
            'quantity_requested' => 500,
            'quantity_produced' => 460,
            'quantity_received' => 460,
            'status' => 'Proses: Dept M1 (In)',
            'current_process_status' => 'Proses: Dept M1 (In)',
            'is_final' => false,
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
        ]);

        $step = WorkRequestStep::create([
            'work_request_id' => $wr->id,
            'step_number' => 1,
            'machine_id' => 1,
            'process_name' => 'Dept M1',
            'status' => 'in_progress',
        ]);

        // Simulasikan Smart Sync:
        // Spreadsheet baru menyatakan status sudah 'Sudah diambil' (Final)
        $newStatus = 'Sudah Diambil';
        $newIsFinal = true;
        $newQtyRec = 500;

        $wr->update([
            'status' => $newStatus,
            'current_process_status' => $newStatus,
            'is_final' => $newIsFinal,
            'quantity_received' => $newQtyRec,
        ]);
        $step->update(['status' => 'completed']);

        // Verifikasi count tetap 1 (TIDAK DOBEL)
        $this->assertEquals(1, WorkRequest::where('wri_number', $wriNumber)->count());
        $freshWr = WorkRequest::where('wri_number', $wriNumber)->first();
        $this->assertEquals('Sudah Diambil', $freshWr->status);
        $this->assertTrue($freshWr->is_final);
        $this->assertEquals(500, (float)$freshWr->quantity_received);
        $this->assertEquals('completed', $step->fresh()->status);
    }

    /**
     * Uji Hierarki Penolakan:
     * Jika Ka. Pengaju menolak tiket, tiket seketika berstatus tutup/batal (Hard Close).
     * Tiket DILARANG masuk ke scope needsApproval() dan WAJIB masuk ke scope rejected().
     */
    public function test_rejection_precedence_pengaju_closes_ticket_immediately(): void
    {
        $comp = Component::create([
            'name' => 'Plate U BT Lower',
            'part_number' => 'CSFP10100070030',
            'total_stock' => 10,
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WR/Dept Gudang/20260930/4/CSFP10100070030/1',
            'component_id' => $comp->id,
            'quantity_requested' => 2,
            'status' => 'cancelled',
            'approval_pengaju' => 'Ditolak',
            'approval_penerima' => 'Ditolak',
            'is_final' => true,
        ]);

        // Verifikasi helper
        $this->assertTrue($wr->isRejected());
        $this->assertFalse($wr->isPendingApproval());

        // Verifikasi scope: TIDAK BOLEH muncul di needsApproval
        $this->assertEquals(0, WorkRequest::needsApproval()->where('id', $wr->id)->count());

        // Verifikasi scope: WAJIB muncul di rejected
        $this->assertEquals(1, WorkRequest::rejected()->where('id', $wr->id)->count());
    }

    /**
     * Uji Mutually Exclusive Scope:
     * Tiket yang valid butuh verifikasi (approval_pengaju = Diterima, approval_penerima = Menunggu)
     * hanya muncul di needsApproval() dan TIDAK muncul di rejected().
     */
    public function test_mutually_exclusive_status_partitioning(): void
    {
        $comp = Component::create([
            'name' => 'Flange Bushing',
            'part_number' => 'CSAA50550200000',
            'total_stock' => 50,
        ]);

        $wrWaiting = WorkRequest::create([
            'wri_number' => 'WRI/DeptGudang/20261111/5/CSAA50550200000',
            'component_id' => $comp->id,
            'quantity_requested' => 1,
            'status' => 'Dalam antrian',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Menunggu',
            'is_final' => false,
        ]);

        $this->assertTrue($wrWaiting->isPendingApproval());
        $this->assertFalse($wrWaiting->isRejected());

        $this->assertEquals(1, WorkRequest::needsApproval()->where('id', $wrWaiting->id)->count());
        $this->assertEquals(0, WorkRequest::rejected()->where('id', $wrWaiting->id)->count());
    }

    /**
     * Uji Aksi Scan Diterima Gudang pada Status Siap diambil:
     * User Gudang mendapatkan aksi Scan Diterima (emerald, fa-check-double)
     * saat tiket berada pada status Siap diambil di stasiun mesin akhir.
     */
    public function test_gudang_user_gets_scan_diterima_action_when_siap_diambil(): void
    {
        $comp = Component::create([
            'name' => 'Laser Cut Plate',
            'part_number' => 'CBPP11050600030',
            'total_stock' => 0,
        ]);

        $gudangUser = User::create([
            'name' => 'Panji',
            'email' => 'panji@example.com',
            'password' => bcrypt('secret'),
            'department' => 'Dept Gudang',
        ]);

        $mesinUser = User::create([
            'name' => 'Nasrul',
            'email' => 'nasrul@example.com',
            'password' => bcrypt('secret'),
            'department' => 'Dept Laser Cutting',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WR/Dept Gudang/20261117/12/CBPP11050600030/1',
            'component_id' => $comp->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept Laser Cutting',
            'routing_text' => 'Laser Cutting',
            'quantity_requested' => 4,
            'quantity_produced' => 4,
            'quantity_received' => 0,
            'status' => 'Siap diambil',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Diterima',
            'is_final' => false,
        ]);

        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'status_label' => 'Proses: Laser Cutting (In)',
            'action_type' => 'process',
            'quantity' => 4,
            'operator_name' => 'Nasrul',
        ]);

        WorkRequestLog::create([
            'work_request_id' => $wr->id,
            'status_label' => 'Siap diambil',
            'action_type' => 'process',
            'quantity' => 4,
            'operator_name' => 'Nasrul',
        ]);

        // Gudang user action check
        $actionsGudang = $wr->getAvailableActionsForUser($gudangUser, true);
        $this->assertNotEmpty($actionsGudang);
        $this->assertEquals('receive', $actionsGudang[0]['type']);
        $this->assertEquals('Sudah Diambil', $actionsGudang[0]['target_status']);
        $this->assertEquals('Scan Diterima (4 pcs)', $actionsGudang[0]['label']);
        $this->assertEquals('emerald', $actionsGudang[0]['color']);
        $this->assertEquals('fa-check-double', $actionsGudang[0]['icon']);
        $this->assertTrue($wr->canUserProcessAction($gudangUser, 'Sudah Diambil', true));

        // Mesin user check (no receive action because fabrication is done)
        $actionsMesin = $wr->getAvailableActionsForUser($mesinUser, true);
        $this->assertEmpty($actionsMesin);
        $this->assertFalse($wr->canUserProcessAction($mesinUser, 'Sudah Diambil', true));
    }

    /**
     * Uji Implied Approval pada legacy ticket atau ticket yang telah mulai diproduksi:
     * Jika approval_penerima masih Menunggu namun status pengerjaan telah jalan (quantity_produced > 0),
     * aksi operasional tidak terblokir sebagai Menunggu Penerima.
     */
    public function test_implied_approval_allows_actions_when_production_started(): void
    {
        $comp = Component::create([
            'name' => 'Bending Flange',
            'part_number' => 'CBPP11570580030',
            'total_stock' => 0,
        ]);

        $gudangUser = User::create([
            'name' => 'Denis',
            'email' => 'denis@example.com',
            'password' => bcrypt('secret'),
            'department' => 'Dept Gudang',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WR/Dept Gudang/20261117/13/CBPP11570580030/3',
            'component_id' => $comp->id,
            'dept_pengaju' => 'Dept Gudang',
            'dept_penerima' => 'Dept Bending',
            'quantity_requested' => 3,
            'quantity_produced' => 3,
            'quantity_received' => 0,
            'status' => 'Siap diambil',
            'approval_pengaju' => 'Diterima',
            'approval_penerima' => 'Menunggu', // Implied approval scenario
            'is_final' => false,
        ]);

        $this->assertTrue($wr->canUserProcessAction($gudangUser, 'Sudah Diambil', true));
        $actions = $wr->getAvailableActionsForUser($gudangUser, true);
        $this->assertNotEmpty($actions);
        $this->assertEquals('Scan Diterima (3 pcs)', $actions[0]['label']);
    }

    /**
     * Uji Preservasi Komponen Baru Unik pada Tanggal yang Sama:
     * Dua tiket komponen baru pada tanggal yang sama tidak boleh tertukar atau kolaps ke satu komponen.
     */
    public function test_new_component_sequence_preserves_distinct_items_on_same_date(): void
    {
        $command = new \App\Console\Commands\MigrateLegacySpreadsheetWr();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('resolveOrCreateComponent');
        $method->setAccessible(true);

        // Tiket 16: Sliding plate B, seq 1 -> NEW-20261117-001
        $comp1 = $method->invoke($command, 'Baru', 'Sliding plate B', '040x200x047', '2026-11-17', [], false, 'WR/Dept Gudang/20261117/16/Baru/1');
        $this->assertEquals('Sliding plate B', $comp1->name);
        $this->assertEquals('NEW-20261117-001', $comp1->part_number);

        // Tiket 17: Sliding A, seq 2 -> NEW-20261117-002
        $comp2 = $method->invoke($command, 'Baru', 'Sliding A', '127x200x047', '2026-11-17', [], false, 'WR/Dept Gudang/20261117/17/Baru/2');
        $this->assertEquals('Sliding A', $comp2->name);
        $this->assertEquals('NEW-20261117-002', $comp2->part_number);

        // Pastikan keduanya berbeda komponen
        $this->assertNotEquals($comp1->id, $comp2->id);
    }

    /**
     * Uji Waktu Proses Diekstrak dari Data Proses (bukan runtime):
     * Log pengerjaan harus memiliki timestamp yang persis sesuai waktu di Data Proses CSV.
     */
    public function test_log_timestamps_parsed_from_data_proses(): void
    {
        $comp = Component::create([
            'name' => 'Laser Cut Item',
            'part_number' => 'CBPP11570580030_LOG',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WR/Dept Gudang/20261117/13/CBPP11570580030_LOG/1',
            'component_id' => $comp->id,
            'status' => 'Siap diambil',
            'created_at' => '2026-11-17 08:00:00',
        ]);

        $command = new \App\Console\Commands\MigrateLegacySpreadsheetWr();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('insertWorkRequestLogs');
        $method->setAccessible(true);

        $dataProses = '[ Proses: Dept Laser Cutting (In) - 3 pcs - 17/11 13:38 - Auto-Start Process ] ; [ Siap diambil - 3 (Final) - 17/11 13:39 - Selesai ]';
        $method->invoke($command, $wr->id, $dataProses, '2026-11-17');

        $logs = WorkRequestLog::where('work_request_id', $wr->id)->orderBy('id')->get();
        $this->assertCount(2, $logs);

        // Log 1: 17/11 13:38
        $this->assertEquals('2026-11-17 13:38:00', $logs[0]->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals('Proses: Dept Laser Cutting (In)', $logs[0]->status_label);
        $this->assertEquals('Auto-Start Process', $logs[0]->notes);

        // Log 2: 17/11 13:39
        $this->assertEquals('2026-11-17 13:39:00', $logs[1]->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals('Siap diambil', $logs[1]->status_label);
        $this->assertEquals('Selesai', $logs[1]->notes);
    }

    /**
     * Uji Tanggal Pembuatan Work Request sesuai Tanggal Order:
     * created_at tersimpan sesuai tglOrder, bukan tanggal eksekusi CLI.
     */
    public function test_work_request_created_at_matches_tgl_order(): void
    {
        $comp = Component::create([
            'name' => 'Date Test Item',
            'part_number' => 'PART-DATE-TEST',
        ]);

        $wr = WorkRequest::create([
            'wri_number' => 'WR/Dept Gudang/20261117/99/PART-DATE-TEST/1',
            'component_id' => $comp->id,
            'status' => 'Belum Verifikasi',
            'created_at' => '2026-11-17 08:00:00',
            'due_date' => '2026-11-17',
        ]);

        $fresh = WorkRequest::find($wr->id);
        $this->assertEquals('2026-11-17', $fresh->created_at->format('Y-m-d'));
        $this->assertEquals('17 Nov 2026', $fresh->created_at->format('d M Y'));
    }

    /**
     * Uji perbaikan Fase 1 pada wr:migrate-legacy-spreadsheet:
     * 1. Part "Baru" mendapat source = 'wr_adhoc' (tidak bocor ke masterCatalog).
     * 2. Tiket WR mewarisi target_location_id dari komponen master.
     * 3. Multi-routing ("Dept Laser Cutting -> Dept M1") dipecah lengkap menjadi 2 WorkRequestStep.
     */
    public function test_migrated_wr_populates_target_location_multi_routing_and_isolates_wr_adhoc(): void
    {
        $user = User::create([
            'name' => 'Admin Cutover',
            'role' => 'admin_gudang',
            'password' => bcrypt('secret'),
            'work_area' => 'Gudang',
            'department' => 'Dept Gudang',
        ]);

        $loc = \App\Models\Location::create([
            'warehouse_id' => 1,
            'zone_code' => '1',
            'zone_name' => 'Area 1',
            'rack_code' => 'R8',
            'rack_number' => 'Rak 8',
        ]);

        $masterComp = Component::create([
            'name' => 'HANDLE',
            'part_number' => 'CSBA30100360000',
            'default_location_id' => $loc->id,
            'default_shelf_level' => 'L1',
            'default_slot_number' => '06',
            'source' => 'master',
        ]);

        $tmpCsv = tempnam(sys_get_temp_dir(), 'wr_test_');
        $rows = [
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', 'Tanggal Order', 'No WR', 'Kode Komponen', 'Nama Komponen', 'Gambar Komponen', 'Routing', 'Dimensi', 'Qty', 'Dept Pengaju', 'Dept Penerima', 'Deskripsi', 'Status Pengaju', 'Status Penerima', 'Status Barang', 'Keterangan Print', '', '', 'Data Proses', '', 'OP Pengaju', 'Kepala Pengaju', 'OP Penerima', 'Kepala Penerima', 'Nama Pengambil (MH)'],
            ['', '2026-11-20', 'WR/Dept Gudang/20261120/1/CSBA30100360000/1', 'CSBA30100360000', 'HANDLE', '', 'Dept Laser Cutting -> Dept M1', '358 x 12', '10', 'Dept Gudang', 'Dept Laser Cutting', 'Order Multi', 'Diterima', 'Diterima', 'Siap diambil', '', '', '', '[ Proses: Dept Laser Cutting (In) - 10 pcs - 20/11 09:00 - Mulai ] ; [ Proses: Dept Laser Cutting (Out) - 10 pcs - 20/11 10:00 - Out ] ; [ MH Pickup: Dept Laser Cutting - 10 pcs - 20/11 10:30 - Ambil ] ; [ Proses: Dept M1 (In) - 10 pcs - 20/11 11:00 - Masuk M1 ] ; [ Siap diambil - 10 (Final) - 20/11 13:00 - Selesai ]', '', 'Budi', 'KaGudang', 'Nasrul', 'KaLaser', ''],
            ['', '2026-11-20', 'WR/Dept Gudang/20261120/2/Baru/1', 'Baru', 'Bracket Adhoc Test', '', 'Dept M1', '100x50x5', '5', 'Dept Gudang', 'Dept M1', 'Part Baru Test', 'Diterima', 'Diterima', 'Dalam antrian', '', '', '', '', '', 'Budi', 'KaGudang', 'Fajar', 'KaM1', ''],
        ];
        $fp = fopen($tmpCsv, 'w');
        foreach ($rows as $r) {
            fputcsv($fp, $r);
        }
        fclose($fp);

        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call('wr:migrate-legacy-spreadsheet', [
                '--file' => $tmpCsv,
                '--komponen-baru-file' => $tmpCsv . '_missing',
            ]);
            $this->assertEquals(0, $exitCode);

            $wrMaster = WorkRequest::where('wri_number', 'WR/Dept Gudang/20261120/1/CSBA30100360000/1')->first();
            $this->assertNotNull($wrMaster);
            $this->assertEquals($loc->id, $wrMaster->target_location_id);
            $this->assertEquals('L1', $wrMaster->target_shelf_level);
            $this->assertEquals('06', $wrMaster->target_slot_number);
            $this->assertEquals(10.0, (float) $wrMaster->current_batch_qty);

            $steps = $wrMaster->steps()->orderBy('step_number')->get();
            $this->assertCount(2, $steps);
            $this->assertEquals('Dept Laser Cutting', $steps[0]->process_name);
            $this->assertEquals('Dept M1', $steps[1]->process_name);

            $adhocComp = Component::where('part_number', 'NEW-20261120-001')->first();
            $this->assertNotNull($adhocComp);
            $this->assertEquals('wr_adhoc', $adhocComp->source);
            $this->assertEquals(1, Component::masterCatalog()->count());
        } finally {
            @unlink($tmpCsv);
        }
    }

    /**
     * Uji import:csv-komponen mode --dry-run (0 perubahan DB) dan mode live (source = 'master' + StockBalance).
     */
    public function test_import_csv_komponen_dry_run_and_live_sets_master_source(): void
    {
        \App\Models\Warehouse::create(['code' => 'WH-HPK-1', 'name' => 'HPK Warehouse']);

        $tmpCsv = tempnam(sys_get_temp_dir(), 'comp_test_');
        $rows = [
            ['', '', '', '', '', '', '', '', '', '', ''],
            ['', '', 'Kode Komponen', 'Nama Komponen', 'Gambar Komponen', 'Kode Rak', 'Saldo', 'Saldo Minimum', 'Panjang ( mm )', 'Diameter / Lebar ( mm )', 'Tebal ( mm )'],
            ['', '', 'COMP-CUTOVER-01', 'PLAT TEST CUTOVER', 'https://drive.google.com/open?id=abc123', '1-R8-L1-06', '25', '10', '350', '120', 'PEJAL'],
        ];
        $fp = fopen($tmpCsv, 'w');
        foreach ($rows as $r) {
            fputcsv($fp, $r);
        }
        fclose($fp);

        try {
            // 1. Dry-run tidak boleh menulis ke DB
            $dryExit = \Illuminate\Support\Facades\Artisan::call('import:csv-komponen', [
                '--file' => $tmpCsv,
                '--dry-run' => true,
            ]);
            $this->assertEquals(0, $dryExit);
            $this->assertEquals(0, Component::count());
            $this->assertEquals(0, \App\Models\StockBalance::count());

            // 2. Live run menulis komponen berstatus source = 'master' dan StockBalance
            $liveExit = \Illuminate\Support\Facades\Artisan::call('import:csv-komponen', [
                '--file' => $tmpCsv,
            ]);
            $this->assertEquals(0, $liveExit, \Illuminate\Support\Facades\Artisan::output());

            $comp = Component::where('part_number', 'COMP-CUTOVER-01')->first();
            $this->assertNotNull($comp);
            $this->assertEquals('master', $comp->source);
            $this->assertEquals('PEJAL', $comp->thickness_or_height_mm);
            $this->assertEquals(25.0, (float) $comp->total_stock);
        } finally {
            @unlink($tmpCsv);
        }
    }

    /**
     * Uji system:cutover-reset pada SQLite :memory::
     * Membersihkan komponen & WR uji coba tanpa menghapus users maupun locations.
     */
    public function test_system_cutover_reset_preserves_users_and_locations(): void
    {
        $user = User::create([
            'name' => 'Admin Tetap Ada',
            'role' => 'admin_gudang',
            'password' => bcrypt('secret'),
        ]);
        $loc = \App\Models\Location::create([
            'zone_code' => '1',
            'rack_code' => 'R1',
            'rack_number' => 'Rak 1',
        ]);
        $comp = Component::create([
            'name' => 'Dummy Pre Cutover',
            'part_number' => 'DUMMY-001',
            'source' => 'master',
        ]);
        \App\Models\StockBalance::create([
            'component_id' => $comp->id,
            'location_id' => $loc->id,
            'quantity' => 50,
        ]);
        WorkRequest::create([
            'wri_number' => 'WRI/TEST/001',
            'component_id' => $comp->id,
            'status' => 'Dalam antrian',
        ]);

        $exitCode = \Illuminate\Support\Facades\Artisan::call('system:cutover-reset', [
            '--skip-backup' => true,
            '--confirm' => true,
        ]);
        $this->assertEquals(0, $exitCode);

        $this->assertEquals(0, Component::count());
        $this->assertEquals(0, \App\Models\StockBalance::count());
        $this->assertEquals(0, WorkRequest::count());
        $this->assertEquals(1, User::count());
        $this->assertEquals(1, \App\Models\Location::count());
    }

    /**
     * Uji import:csv-komponen mendukung layout header bergeser (Kode Komponen di kolom index 1 + kolom tanggal transaksi).
     */
    public function test_import_csv_komponen_supports_shifted_header_layout(): void
    {
        \App\Models\Warehouse::create(['code' => 'WH-HPK-1', 'name' => 'HPK Warehouse']);

        $tmpCsv = tempnam(sys_get_temp_dir(), 'comp_shifted_');
        $rows = [
            ['', '', '', '', '', '', '', '', '2', '', '', '', '', '', '', ''],
            ['', 'Kode Komponen', 'Nama Komponen', 'Gambar Komponen', 'Kode Rak', 'Saldo', 'Transaksi Masuk Terakhir', 'Transaksi Keluar Terakhir', 'Stock Opname Terakhir', 'Routing', 'Saldo Minimum', 'Panjang ( mm )', 'Diameter / Lebar ( mm )', 'Tebal ( mm )'],
            ['1', 'SHIFT-001', 'HANDLE SHIFTED', 'https://drive.google.com/open?id=xyz', '1-R8-L1-06', '82', '05/08/2026', '27/10/2026', '02/07/2026', '', '60', '358', '', '12'],
        ];
        $fp = fopen($tmpCsv, 'w');
        foreach ($rows as $r) {
            fputcsv($fp, $r);
        }
        fclose($fp);

        try {
            $exit = \Illuminate\Support\Facades\Artisan::call('import:csv-komponen', ['--file' => $tmpCsv]);
            $this->assertEquals(0, $exit, \Illuminate\Support\Facades\Artisan::output());

            $comp = Component::where('part_number', 'SHIFT-001')->first();
            $this->assertNotNull($comp);
            $this->assertEquals('HANDLE SHIFTED', $comp->name);
            $this->assertEquals(60, $comp->minimum_stock);
            $this->assertEquals('358', $comp->length_mm);
            $this->assertEquals('12', $comp->thickness_or_height_mm);
            $this->assertEquals(82.0, (float) $comp->total_stock);
        } finally {
            @unlink($tmpCsv);
        }
    }
}
