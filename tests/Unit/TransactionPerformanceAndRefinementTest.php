<?php

namespace Tests\Unit;

use App\Http\Controllers\TransactionController;
use App\Models\Component;
use App\Models\Location;
use App\Models\StockBalance;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\WorkRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionPerformanceAndRefinementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('staff');
            $table->string('work_area')->nullable();
            $table->string('department')->nullable();
            $table->boolean('can_access_all_departments')->default(true);
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

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->string('zone_code')->nullable();
            $table->string('zone_name')->nullable();
            $table->string('aisle')->nullable();
            $table->string('rack_number')->nullable();
            $table->string('rack_code')->nullable();
            $table->string('bin_level')->nullable();
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->nullable();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->string('category')->default('karoseri');
            $table->string('uom')->default('PCS');
            $table->decimal('total_stock', 15, 2)->default(0);
            $table->decimal('minimum_stock', 15, 2)->default(0);
            $table->unsignedBigInteger('default_location_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('work_requests', function (Blueprint $table) {
            $table->id();
            $table->string('wri_number')->unique();
            $table->unsignedBigInteger('component_id')->nullable();
            $table->string('spk_reference')->nullable();
            $table->decimal('quantity_requested', 15, 2)->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->string('type');
            $table->string('spk_number')->nullable();
            $table->string('reference_document')->nullable();
            $table->unsignedBigInteger('work_station_id')->nullable();
            $table->unsignedBigInteger('work_request_id')->nullable();
            $table->string('recipient_name')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->date('transaction_date');
            $table->string('status')->default('completed');
            $table->timestamps();
        });

        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('component_id');
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('component_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->string('shelf_level')->nullable();
            $table->string('slot_number')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('module')->nullable();
            $table->string('action_type')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    public function test_transaction_button_has_no_double_plus(): void
    {
        $indexPath = realpath(__DIR__ . '/../../resources/views/transactions/index.blade.php');
        $this->assertNotFalse($indexPath);
        $content = file_get_contents($indexPath);

        // Verifies no duplicate plus symbol
        $this->assertStringNotContainsString('<span>+ Transaksi Baru</span>', $content);
        $this->assertStringContainsString('<span>Transaksi Baru</span>', $content);
    }

    public function test_transaction_model_is_from_work_request_and_type_badge(): void
    {
        $user = User::create([
            'name' => 'Panji Gudang',
            'email' => 'panji@test.com',
            'password' => bcrypt('password'),
        ]);

        $wrTx = Transaction::create([
            'transaction_number' => 'TRX/IN-WRI/20261111/ABC12',
            'type' => 'inbound',
            'reference_document' => 'WRI/202611/001',
            'work_request_id' => 99,
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->assertTrue($wrTx->is_from_work_request);
        $wrBadge = $wrTx->type_badge;
        $this->assertEquals('Inbound (Hasil WR)', $wrBadge['label']);
        $this->assertEquals('fa-industry', $wrBadge['icon']);
        $this->assertEquals('wr', $wrBadge['channel']);

        $manualTx = Transaction::create([
            'transaction_number' => 'TRX-IN-202611-XYZ89',
            'type' => 'inbound',
            'reference_document' => 'PO/2026/999',
            'work_request_id' => null,
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->assertFalse($manualTx->is_from_work_request);
        $manualBadge = $manualTx->type_badge;
        $this->assertEquals('Inbound (Langsung)', $manualBadge['label']);
        $this->assertEquals('fa-truck-ramp-box', $manualBadge['icon']);
        $this->assertEquals('manual', $manualBadge['channel']);
    }

    public function test_inbound_wr_and_inbound_manual_filtering(): void
    {
        $user = User::create([
            'name' => 'Operator Test',
            'email' => 'op@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        // 1. Inbound from WR
        Transaction::create([
            'transaction_number' => 'TRX/IN-WRI/20261111/11111',
            'type' => 'inbound',
            'reference_document' => 'WRI/DeptGudang/20261111/1',
            'work_request_id' => 101,
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
        ]);

        // 2. Inbound manual
        Transaction::create([
            'transaction_number' => 'TRX-IN-202611-22222',
            'type' => 'inbound',
            'reference_document' => 'PO/VENDOR/123',
            'work_request_id' => null,
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
        ]);

        // 3. Outbound
        Transaction::create([
            'transaction_number' => 'TRX-OUT-202611-33333',
            'type' => 'outbound',
            'reference_document' => 'SPK/KAROSERI/777',
            'work_request_id' => null,
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
        ]);

        $controller = app(TransactionController::class);

        // Filter: inbound_wr
        $reqWr = Request::create('/transactions', 'GET', ['type' => 'inbound_wr']);
        $viewWr = $controller->index($reqWr);
        $txsWr = $viewWr->getData()['transactions'];
        $this->assertEquals(1, $txsWr->total());
        $this->assertEquals('TRX/IN-WRI/20261111/11111', $txsWr->first()->transaction_number);

        // Filter: inbound_manual
        $reqManual = Request::create('/transactions', 'GET', ['type' => 'inbound_manual']);
        $viewManual = $controller->index($reqManual);
        $txsManual = $viewManual->getData()['transactions'];
        $this->assertEquals(1, $txsManual->total());
        $this->assertEquals('TRX-IN-202611-22222', $txsManual->first()->transaction_number);

        // Filter: inbound (all inbound)
        $reqAllIn = Request::create('/transactions', 'GET', ['type' => 'inbound']);
        $viewAllIn = $controller->index($reqAllIn);
        $txsAllIn = $viewAllIn->getData()['transactions'];
        $this->assertEquals(2, $txsAllIn->total());
    }

    public function test_store_inbound_sanitizes_from_location_to_null(): void
    {
        $user = User::create([
            'name' => 'Admin Gudang',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        $comp = Component::create([
            'part_number' => 'PN-TEST-100',
            'name' => 'Handle Pintu Belakang',
            'uom' => 'PCS',
        ]);

        $loc = Location::create([
            'rack_number' => 'Rak 8',
            'rack_code' => 'R8',
        ]);

        $request = Request::create('/transactions', 'POST', [
            'type' => 'inbound',
            'reference_document' => 'PO/SUPPLIER/2026',
            'items' => [
                [
                    'component_id' => $comp->id,
                    'from_location_id' => $loc->id, // Mistakenly sent
                    'to_location_id' => $loc->id,
                    'quantity' => 50,
                ],
            ],
        ]);

        $controller = app(TransactionController::class);
        $controller->store($request);

        $item = TransactionItem::first();
        $this->assertNotNull($item);
        // from_location_id MUST be normalized to null for Inbound
        $this->assertNull($item->from_location_id, 'Inbound transactions must have null from_location_id');
        $this->assertEquals($loc->id, $item->to_location_id);
    }

    public function test_show_and_print_sheet_views_render_contextual_signatures_and_racks(): void
    {
        $showPath = realpath(__DIR__ . '/../../resources/views/transactions/show.blade.php');
        $this->assertNotFalse($showPath);
        $showContent = file_get_contents($showPath);

        // 1. Contextual Inbound signature
        $this->assertStringContainsString('Diserahkan Oleh (Supplier / Kurir):', $showContent);
        $this->assertStringContainsString('Diterima Oleh (Gudang):', $showContent);

        // 2. Anti-overflow signature line
        $this->assertStringContainsString('( Nama Terang &amp; TTD )', $showContent);
        $this->assertStringNotContainsString('( .................................... )', $showContent);

        // 3. Contextual column headers
        $this->assertStringContainsString('Asal Pengiriman (Vendor/WR)', $showContent);
        $this->assertStringContainsString('Rak Penerimaan Gudang', $showContent);

        // 4. Print sheet synchronization
        $printPath = realpath(__DIR__ . '/../../resources/views/transactions/print_sheet.blade.php');
        $this->assertNotFalse($printPath);
        $printContent = file_get_contents($printPath);

        $this->assertStringContainsString('Diserahkan Oleh (Supplier / Kurir):', $printContent);
        $this->assertStringNotContainsString('( .................................... )', $printContent);
    }
}
