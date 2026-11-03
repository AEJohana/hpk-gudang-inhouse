<?php

namespace Tests\Unit;

use App\Models\Component;
use App\Models\Location;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransactionFormSimplificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('department')->nullable();
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

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('zone_id')->constrained('zones');
            $table->string('zone_code')->nullable();
            $table->string('zone_name')->nullable();
            $table->string('aisle')->nullable();
            $table->string('rack_number')->nullable();
            $table->string('storage_type')->default('rack');
            $table->string('rack_code')->nullable();
            $table->string('bin_level')->nullable();
            $table->integer('total_levels')->default(4);
            $table->integer('slots_per_level')->default(6);
            $table->json('level_slots_config')->nullable();
            $table->text('description')->nullable();
            $table->integer('max_capacity')->default(60);
            $table->integer('grid_x')->default(1);
            $table->integer('grid_y')->default(1);
            $table->integer('grid_w')->default(1);
            $table->integer('grid_h')->default(1);
            $table->string('color')->default('blue');
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->string('category')->default('karoseri');
            $table->string('uom')->default('PCS');
            $table->text('specification')->nullable();
            $table->decimal('total_stock', 15, 2)->default(0);
            $table->decimal('minimum_stock', 15, 2)->default(0);
            $table->decimal('maximum_stock', 15, 2)->default(100);
            $table->foreignId('default_location_id')->nullable()->constrained('locations');
            $table->string('default_shelf_level')->nullable();
            $table->string('default_slot_number')->nullable();
            $table->string('image_path')->nullable();
            $table->text('qr_code_payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->string('type');
            $table->string('spk_number')->nullable();
            $table->string('reference_document')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->date('transaction_date');
            $table->string('status')->default('completed');
            $table->timestamps();
        });

        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('components');
            $table->foreignId('from_location_id')->nullable()->constrained('locations');
            $table->foreignId('to_location_id')->nullable()->constrained('locations');
            $table->decimal('quantity', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained('components');
            $table->foreignId('location_id')->nullable()->constrained('locations');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Test index view renders unified '+ Transaksi' button instead of 3 separate buttons.
     */
    public function test_index_view_renders_unified_single_transaction_button(): void
    {
        $role = Role::create(['name' => 'admin_gudang', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'Pak Panji',
            'email' => 'panji@hpk.test',
            'password' => bcrypt('secret'),
        ]);
        $user->assignRole($role);
        $this->actingAs($user);

        $transactions = Transaction::paginate(15);
        $view = $this->view('transactions.index', compact('transactions'));

        // Assert single unified button
        $view->assertSee('<span>Transaksi</span>', false);
        $view->assertSee(route('transactions.create'));

        // Assert obsolete 3 sub-menu buttons are eliminated from header
        $view->assertDontSee("route('transactions.create', ['type' => 'inbound'])", false);
        $view->assertDontSee("route('transactions.create', ['type' => 'outbound'])", false);
        $view->assertDontSee("route('transactions.create', ['type' => 'transfer'])", false);
    }

    /**
     * Test create view has 3 dropdown choices, readonly real-time date, no SPK or PO fields, and rack fallback.
     */
    public function test_create_view_has_simplified_fields_and_rack_fallback(): void
    {
        $role = Role::create(['name' => 'admin_gudang', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'Pak Panji',
            'email' => 'panji@hpk.test',
            'password' => bcrypt('secret'),
        ]);
        $user->assignRole($role);
        $this->actingAs($user);

        $defaultType = 'inbound';
        $locations = collect();
        $components = collect();

        $view = $this->view('transactions.create', compact('defaultType', 'locations', 'components'));

        // 1. Assert 3 transaction type options
        $view->assertSee('Inbound (Barang Masuk / Penerimaan)');
        $view->assertSee('Outbound (Barang Keluar / Perakitan)');
        $view->assertSee('Transfer Antar Rak / Zona');

        // 2. Assert SPK Karoseri and Surat Jalan / PO fields are removed
        $view->assertDontSee('name="spk_number"', false);
        $view->assertDontSee('name="reference_document"', false);
        $view->assertDontSee('No. SPK Karoseri');
        $view->assertDontSee('No. Surat Jalan / PO');

        // 3. Assert transaction_date is readonly with real-time lock
        $view->assertSee('name="transaction_date"', false);
        $view->assertSee('readonly', false);
        $view->assertSee('Real-time');

        // 4. Assert rack fallback option exists
        $view->assertSee('-- Tidak Ada Rak Terdaftar --');
        $view->assertSee('-- Pilih Rak Tujuan --');
    }

    /**
     * Test TransactionController store forces real-time server date.
     */
    public function test_store_forces_realtime_server_date_and_handles_null_spk(): void
    {
        $role = Role::create(['name' => 'admin_gudang', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'Pak Panji',
            'email' => 'panji@hpk.test',
            'password' => bcrypt('secret'),
        ]);
        $user->assignRole($role);
        $this->actingAs($user);

        $component = Component::create([
            'part_number' => 'TST-001',
            'name' => 'Hydraulic Cylinder Test',
            'total_stock' => 10,
        ]);

        $response = $this->post(route('transactions.store'), [
            'type' => 'inbound',
            'notes' => 'Penerimaan komponen baru',
            'items' => [
                [
                    'component_id' => $component->id,
                    'quantity' => 5,
                ]
            ],
        ]);

        $this->assertDatabaseHas('transactions', [
            'type' => 'inbound',
            'spk_number' => null,
            'reference_document' => null,
            'notes' => 'Penerimaan komponen baru',
            'user_id' => $user->id,
        ]);

        $tx = Transaction::first();
        $this->assertNotNull($tx);
        $this->assertEquals(now()->toDateString(), $tx->transaction_date->toDateString());
    }
}
