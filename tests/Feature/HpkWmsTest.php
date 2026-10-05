<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Component;
use App\Models\Location;
use App\Models\Warehouse;
use App\Models\Machine;
use App\Models\WorkStation;
use App\Models\Setting;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HpkWmsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::where('role', 'admin_gudang')->first() ?? User::first();
    }

    public function test_login_page_renders_with_hpk_branding(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('HYDRAXLE PERKASA');
        $response->assertSee('logo_hpk.webp');
    }

    public function test_dashboard_renders_command_center(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('WAREHOUSE MANAGEMENT SYSTEM');
        $response->assertSee('ZONA A');
        $response->assertSee('ZONA B');
    }

    public function test_components_index_and_search_api(): void
    {
        $response = $this->actingAs($this->user)->get('/components');
        $response->assertStatus(200);
        $response->assertSee('HYD-CYL-160');

        $apiResponse = $this->actingAs($this->user)->getJson('/api/components/search?q=HYD-CYL-160');
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonPath('success', true);
        $apiResponse->assertJsonPath('data.part_number', 'HYD-CYL-160');
    }

    public function test_transactions_index_and_create(): void
    {
        $response = $this->actingAs($this->user)->get('/transactions');
        $response->assertStatus(200);

        $createResponse = $this->actingAs($this->user)->get('/transactions/create');
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Identifikasi Komponen');
    }

    public function test_ecrs_module(): void
    {
        $response = $this->actingAs($this->user)->get('/ecrs');
        $response->assertStatus(200);
        $response->assertSee('ECR-2026-10-001');
    }

    public function test_disposals_module(): void
    {
        $response = $this->actingAs($this->user)->get('/disposals');
        $response->assertStatus(200);
        $response->assertSee('DSP-2026-10-001');
    }

    public function test_qr_requests_and_thermal_print(): void
    {
        $response = $this->actingAs($this->user)->get('/qr-requests');
        $response->assertStatus(200);
        $response->assertSee('QR-REQ-202610-001');
    }

    public function test_cycle_counts_module(): void
    {
        $response = $this->actingAs($this->user)->get('/cycle-counts');
        $response->assertStatus(200);
        $response->assertSee('CC-202610-001');
    }

    public function test_warehouse_map_module(): void
    {
        $response = $this->actingAs($this->user)->get('/warehouse-map');
        $response->assertStatus(200);
        $response->assertSee('Interactive Lego 2D Floor Plan');
        $response->assertSee('Rak 3');
        $response->assertSee('Rak 5');

        $rak3 = Location::where('rack_number', 'Rak 3')->first();
        $this->assertNotNull($rak3);

        // Verify Rak 3 has multi-components including MOUNTING HINO 2
        $this->assertGreaterThanOrEqual(2, $rak3->stockBalances()->count());

        // Test quick detail API for Rak 3
        $quickResponse = $this->actingAs($this->user)->getJson("/api/locations/{$rak3->id}/quick-detail");
        $quickResponse->assertStatus(200);
        $quickResponse->assertJsonPath('rack_number', 'Rak 3');
        $quickResponse->assertJsonPath('full_rack_code', '1-R3');
        $quickResponse->assertJsonFragment([
            'part_number' => 'CSFP10300250081',
            'name' => 'MOUNTING HINO 2',
            'specific_location_code' => '1-R3-L3-05',
        ]);

        // Test save layout API (1x1 format)
        $saveResponse = $this->actingAs($this->user)->postJson('/warehouse-map/save-layout', [
            'layout' => [
                [
                    'id' => $rak3->id,
                    'grid_x' => 4,
                    'grid_y' => 5,
                    'grid_w' => 1,
                    'grid_h' => 1,
                ],
            ],
        ]);
        $saveResponse->assertStatus(200);
        $saveResponse->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('locations', [
            'id' => $rak3->id,
            'grid_x' => 4,
            'grid_y' => 5,
            'grid_w' => 1,
            'grid_h' => 1,
        ]);

        // Test reset layout API
        $resetResponse = $this->actingAs($this->user)->postJson('/warehouse-map/reset-layout');
        $resetResponse->assertStatus(200);
        $resetResponse->assertJsonPath('status', 'success');

        // Test single location show page
        $showResponse = $this->actingAs($this->user)->get("/locations/{$rak3->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Rak 3');
        $showResponse->assertSee('1-R3-L3-05');
        $showResponse->assertSee('MOUNTING HINO 2');
    }

    public function test_warehouse_area_dimension_and_pallet_storage(): void
    {
        $warehouse1 = Warehouse::where('code', 'WH-HPK-1')->first();
        $warehouse2 = Warehouse::where('code', 'WH-HPK-2')->first();
        $this->assertNotNull($warehouse1);
        $this->assertNotNull($warehouse2);

        // 1. Check index displays warehouse area badges and pallet locations
        $response = $this->actingAs($this->user)->get('/warehouse-map');
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Luas Gedung');
        $response->assertSee('Pallet');
        $response->assertSee('Pallet 1');

        // 2. Warehouse switching via query param
        $wh2Response = $this->actingAs($this->user)->get("/warehouse-map?warehouse_id={$warehouse2->id}");
        $wh2Response->assertStatus(200);
        $wh2Response->assertSee($warehouse2->code);
        $wh2Response->assertSee('WH-HPK-2');

        // 3. Test update warehouse area dimensions API
        $updateAreaResponse = $this->actingAs($this->user)->postJson("/warehouse-map/warehouses/{$warehouse1->id}/update-area", [
            'width_meters' => 30.00,
            'length_meters' => 45.00,
            'grid_columns' => 18,
            'grid_rows' => 14,
        ]);
        $updateAreaResponse->assertStatus(200);
        $updateAreaResponse->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse1->id,
            'width_meters' => 30.00,
            'length_meters' => 45.00,
            'grid_columns' => 18,
            'grid_rows' => 14,
        ]);

        // 4. Test create new 1x1 Pallet location
        $createPalletResponse = $this->actingAs($this->user)->postJson('/warehouse-map/create-location', [
            'warehouse_id' => $warehouse1->id,
            'storage_type' => 'pallet',
            'rack_number' => 'Pallet 99',
            'zone' => 'ZONA B',
            'levels' => 1,
            'slots_per_level' => 1,
            'max_weight_kg' => 1500,
        ]);
        $createPalletResponse->assertStatus(200);
        $createPalletResponse->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('locations', [
            'warehouse_id' => $warehouse1->id,
            'storage_type' => 'pallet',
            'rack_number' => 'Pallet 99',
            'grid_w' => 1,
            'grid_h' => 1,
        ]);

        // 5. Test create new 1x1 Rack location
        $createRackResponse = $this->actingAs($this->user)->postJson('/warehouse-map/create-location', [
            'warehouse_id' => $warehouse1->id,
            'storage_type' => 'rack',
            'rack_number' => 'Rak 99',
            'zone' => 'ZONA A',
            'levels' => 4,
            'slots_per_level' => 6,
            'max_weight_kg' => 3000,
        ]);
        $createRackResponse->assertStatus(200);
        $createRackResponse->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('locations', [
            'warehouse_id' => $warehouse1->id,
            'storage_type' => 'rack',
            'rack_number' => 'Rak 99',
            'grid_w' => 1,
            'grid_h' => 1,
        ]);

        // 6. Test quick detail API on seeded Pallet 1 holding HYD-CYL-200
        $pallet1 = Location::where('warehouse_id', $warehouse1->id)
            ->where('rack_number', 'Pallet 1')
            ->first();
        $this->assertNotNull($pallet1);

        $palletDetail = $this->actingAs($this->user)->getJson("/api/locations/{$pallet1->id}/quick-detail");
        $palletDetail->assertStatus(200);
        $palletDetail->assertJsonPath('storage_type', 'pallet');
        $palletDetail->assertJsonPath('is_pallet', true);
        $palletDetail->assertJsonPath('display_type_label', 'Penyimpanan Lantai Pallet (Non-Rak)');
        $palletDetail->assertJsonFragment([
            'part_number' => 'HYD-CYL-200',
        ]);
    }

    public function test_flexible_rack_shape_and_levels(): void
    {
        $rak3 = Location::where('rack_number', 'Rak 3')->first();
        $this->assertNotNull($rak3);

        // 1. Update Rak 3 shape to 2x1 and levels to 3
        $updateRakResponse = $this->actingAs($this->user)->postJson("/warehouse-map/locations/{$rak3->id}/update-config", [
            'grid_w' => 2,
            'grid_h' => 1,
            'total_levels' => 3,
            'slots_per_level' => 6,
        ]);
        $updateRakResponse->assertStatus(200);
        $updateRakResponse->assertJsonPath('status', 'success');
        $updateRakResponse->assertJsonPath('location.grid_w', 2);
        $updateRakResponse->assertJsonPath('location.grid_h', 1);
        $updateRakResponse->assertJsonPath('location.total_levels', 3);

        // Check slot_matrix in response has exactly 3 levels (L3, L2, L1)
        $matrix = $updateRakResponse->json('location.slot_matrix');
        $this->assertCount(3, $matrix);
        $this->assertEquals(3, $matrix[0]['level']);
        $this->assertEquals(2, $matrix[1]['level']);
        $this->assertEquals(1, $matrix[2]['level']);

        $this->assertDatabaseHas('locations', [
            'id' => $rak3->id,
            'grid_w' => 2,
            'grid_h' => 1,
            'total_levels' => 3,
        ]);

        // 2. Update Rak 5 to vertical 1x2 and 2 levels
        $rak5 = Location::where('rack_number', 'Rak 5')->first();
        $this->assertNotNull($rak5);

        $updateRak5Response = $this->actingAs($this->user)->postJson("/warehouse-map/locations/{$rak5->id}/update-config", [
            'grid_w' => 1,
            'grid_h' => 2,
            'total_levels' => 2,
            'slots_per_level' => 4,
        ]);
        $updateRak5Response->assertStatus(200);
        $updateRak5Response->assertJsonPath('location.grid_w', 1);
        $updateRak5Response->assertJsonPath('location.grid_h', 2);
        $updateRak5Response->assertJsonPath('location.total_levels', 2);

        $matrix5 = $updateRak5Response->json('location.slot_matrix');
        $this->assertCount(2, $matrix5);

        // 3. Update Pallet 1 to 2x2 large floor block
        $pallet1 = Location::where('rack_number', 'Pallet 1')->first();
        $this->assertNotNull($pallet1);

        $updatePalletResponse = $this->actingAs($this->user)->postJson("/warehouse-map/locations/{$pallet1->id}/update-config", [
            'grid_w' => 2,
            'grid_h' => 2,
        ]);
        $updatePalletResponse->assertStatus(200);
        $updatePalletResponse->assertJsonPath('location.grid_w', 2);
        $updatePalletResponse->assertJsonPath('location.grid_h', 2);

        $this->assertDatabaseHas('locations', [
            'id' => $pallet1->id,
            'grid_w' => 2,
            'grid_h' => 2,
        ]);

        // 4. Save multi-cell layout coordinates via saveLayout
        $saveResponse = $this->actingAs($this->user)->postJson('/warehouse-map/save-layout', [
            'layout' => [
                [
                    'id' => $rak3->id,
                    'grid_x' => 5,
                    'grid_y' => 6,
                    'grid_w' => 2,
                    'grid_h' => 1,
                ],
                [
                    'id' => $pallet1->id,
                    'grid_x' => 8,
                    'grid_y' => 8,
                    'grid_w' => 2,
                    'grid_h' => 2,
                ],
            ],
        ]);
        $saveResponse->assertStatus(200);
        $this->assertDatabaseHas('locations', [
            'id' => $rak3->id,
            'grid_x' => 5,
            'grid_y' => 6,
            'grid_w' => 2,
            'grid_h' => 1,
        ]);
        $this->assertDatabaseHas('locations', [
            'id' => $pallet1->id,
            'grid_x' => 8,
            'grid_y' => 8,
            'grid_w' => 2,
            'grid_h' => 2,
        ]);

        // 5. Create new location with custom shape 3x1 and 3 levels
        $createResponse = $this->actingAs($this->user)->postJson('/warehouse-map/create-location', [
            'warehouse_id' => $rak3->warehouse_id,
            'storage_type' => 'rack',
            'rack_number' => 'Rak Kustom 1',
            'grid_w' => 3,
            'grid_h' => 1,
            'levels' => 3,
            'slots_per_level' => 6,
        ]);
        $createResponse->assertStatus(200);
        $this->assertDatabaseHas('locations', [
            'rack_number' => 'Rak Kustom 1',
            'grid_w' => 3,
            'grid_h' => 1,
            'total_levels' => 3,
        ]);
    }

    public function test_varying_slots_per_level(): void
    {
        $rak = Location::where('storage_type', 'rack')->first();
        $this->assertNotNull($rak);

        // Update rack to 3 levels with varying slots per level:
        // Level 1: 2 slots, Level 2: 4 slots, Level 3: 6 slots
        $levelSlotsConfig = [
            1 => 2,
            2 => 4,
            3 => 6,
        ];

        $response = $this->actingAs($this->user)->postJson("/warehouse-map/locations/{$rak->id}/update-config", [
            'grid_w' => 2,
            'grid_h' => 1,
            'total_levels' => 3,
            'slots_per_level' => 6,
            'level_slots_config' => $levelSlotsConfig,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('location.total_levels', 3);
        $response->assertJsonPath('location.level_slots_config.1', 2);
        $response->assertJsonPath('location.level_slots_config.2', 4);
        $response->assertJsonPath('location.level_slots_config.3', 6);
        // max capacity is sum(2+4+6) * 2 = 24
        $response->assertJsonPath('location.max_capacity', 24);

        $rak->refresh();
        $this->assertEquals(2, $rak->getSlotsForLevel(1));
        $this->assertEquals(4, $rak->getSlotsForLevel(2));
        $this->assertEquals(6, $rak->getSlotsForLevel(3));

        // Check slot matrix structure
        $slotMatrix = $rak->slot_matrix;
        $this->assertCount(3, $slotMatrix);

        // Level 3 should have 6 slots
        $this->assertEquals(3, $slotMatrix[0]['level']);
        $this->assertCount(6, $slotMatrix[0]['slots']);
        $this->assertEquals('06', $slotMatrix[0]['slots'][5]['slot_number']);

        // Level 2 should have 4 slots
        $this->assertEquals(2, $slotMatrix[1]['level']);
        $this->assertCount(4, $slotMatrix[1]['slots']);
        $this->assertEquals('04', $slotMatrix[1]['slots'][3]['slot_number']);

        // Level 1 should have 2 slots
        $this->assertEquals(1, $slotMatrix[2]['level']);
        $this->assertCount(2, $slotMatrix[2]['slots']);
        $this->assertEquals('02', $slotMatrix[2]['slots'][1]['slot_number']);

        // Test quick detail API returns slot matrix with dynamic slots
        $quickResponse = $this->actingAs($this->user)->getJson("/api/locations/{$rak->id}/quick-detail");
        $quickResponse->assertStatus(200);
        $quickResponse->assertJsonPath('id', $rak->id);
        $quickResponse->assertJsonPath('level_slots_config.1', 2);
        $quickResponse->assertJsonCount(3, 'slot_matrix');
        $quickResponse->assertJsonCount(6, 'slot_matrix.0.slots');
        $quickResponse->assertJsonCount(4, 'slot_matrix.1.slots');
        $quickResponse->assertJsonCount(2, 'slot_matrix.2.slots');
    }

    public function test_admin_panel_routes(): void
    {
        $adminRoutes = [
            '/admin/dashboard',
            '/admin/users',
            '/admin/users/create',
            '/admin/roles',
            '/admin/component-categories',
            '/admin/component-categories/create',
            '/admin/uoms',
            '/admin/uoms/create',
            '/admin/locations-master',
            '/admin/settings',
            '/admin/audit-logs',
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->actingAs($this->user)->get($route);
            $response->assertStatus(200);
        }

        // Test edit routes
        $firstUser = \App\Models\User::first();
        $this->actingAs($this->user)->get("/admin/users/{$firstUser->id}/edit")->assertStatus(200);

        $firstRole = \Spatie\Permission\Models\Role::first();
        $this->actingAs($this->user)->get("/admin/roles/{$firstRole->id}/edit")->assertStatus(200);

        $firstCat = \App\Models\ComponentCategory::first();
        $this->actingAs($this->user)->get("/admin/component-categories/{$firstCat->id}/edit")->assertStatus(200);

        $firstUom = \App\Models\Uom::first();
        $this->actingAs($this->user)->get("/admin/uoms/{$firstUom->id}/edit")->assertStatus(200);
    }

    public function test_master_component_show_and_edit_views_render_without_applayout_conversion_error(): void
    {
        $comp = Component::first();
        $this->assertNotNull($comp);

        // Verify show page renders 200 without AppLayout to string conversion error
        $showResponse = $this->actingAs($this->user)->get("/components/{$comp->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee($comp->part_number);

        // Verify edit page renders 200 without AppLayout to string conversion error
        $editResponse = $this->actingAs($this->user)->get("/components/{$comp->id}/edit");
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Edit Data Komponen');
    }

    public function test_work_request_inhouse_machine_routing_and_auto_stock_update(): void
    {
        $comp = Component::where('part_number', 'CSFP10300250081')->first() ?? Component::first();
        $rak3 = Location::where('rack_number', 'Rak 3')->first() ?? Location::first();
        $laser = \App\Models\Machine::where('code', 'MC-LC-01')->first() ?? \App\Models\Machine::first();
        $bending = \App\Models\Machine::where('code', 'MC-BND-01')->first() ?? \App\Models\Machine::skip(1)->first();

        $initialBalance = \App\Models\StockBalance::where('component_id', $comp->id)
            ->where('location_id', $rak3->id)
            ->first();
        $initialQty = $initialBalance ? (float) $initialBalance->quantity : 0;

        // 1. Create Work Request Inhouse (Gudang order ke Machine Center)
        $createResponse = $this->actingAs($this->user)->post('/work-requests', [
            'component_id' => $comp->id,
            'quantity_requested' => 25,
            'target_location_id' => $rak3->id,
            'target_shelf_level' => 'L3',
            'target_slot_number' => '05',
            'priority' => 'high',
            'due_date' => now()->addDays(2)->format('Y-m-d'),
            'spk_reference' => 'SPK-TEST-HINO-500',
            'notes' => 'Testing routing mesin dan auto stock update',
            'steps' => [
                [
                    'machine_id' => $laser->id,
                    'process_name' => 'Potong Laser Plat Sasis 8mm',
                ],
                [
                    'machine_id' => $bending->id,
                    'process_name' => 'Bending Flange Sudut 90 Derajat',
                ],
            ],
        ]);

        $createResponse->assertStatus(302);

        $wri = \App\Models\WorkRequest::where('spk_reference', 'SPK-TEST-HINO-500')->first();
        $this->assertNotNull($wri);
        $this->assertEquals('in_production', $wri->status);
        $this->assertCount(2, $wri->steps);

        $step1 = $wri->steps()->where('step_number', 1)->first();
        $step2 = $wri->steps()->where('step_number', 2)->first();
        $this->assertEquals('in_progress', $step1->status);
        $this->assertEquals('pending', $step2->status);

        // 2. Advance Step 1 (Laser Cutting complete) -> Step 2 automatically becomes in_progress
        $advStep1Response = $this->actingAs($this->user)->post("/work-requests/{$wri->id}/steps/{$step1->id}/advance", [
            'action' => 'complete',
            'operator_name' => 'Joko Sutrisno',
            'notes' => 'Potong laser 25 pcs tuntas',
        ]);
        $advStep1Response->assertStatus(302);

        $step1->refresh();
        $step2->refresh();
        $this->assertEquals('completed', $step1->status);
        $this->assertEquals('in_progress', $step2->status);

        // 3. Advance Step 2 (Bending complete) -> WRI becomes ready_for_warehouse
        $advStep2Response = $this->actingAs($this->user)->post("/work-requests/{$wri->id}/steps/{$step2->id}/advance", [
            'action' => 'complete',
            'operator_name' => 'Bambang Irawan',
            'notes' => 'Bending 25 pcs tuntas',
        ]);
        $advStep2Response->assertStatus(302);

        $step2->refresh();
        $wri->refresh();
        $this->assertEquals('completed', $step2->status);
        $this->assertEquals('ready_for_warehouse', $wri->status);
        $this->assertEquals(25, (float) $wri->quantity_produced);

        // 4. Gudang receives items (Putaway) -> updates stock balance and creates inbound transaction
        $receiveResponse = $this->actingAs($this->user)->post("/work-requests/{$wri->id}/receive", [
            'quantity_received' => 25,
            'target_location_id' => $rak3->id,
            'target_shelf_level' => 'L3',
            'target_slot_number' => '05',
            'batch_lot_number' => 'LOT-202610-TESTWRI',
            'notes' => 'Barang diterima lengkap dan lolos visual QC',
        ]);
        $receiveResponse->assertStatus(302);

        $wri->refresh();
        $this->assertEquals('received', $wri->status);
        $this->assertEquals(25, (float) $wri->quantity_received);

        // Assert Stock Balance is increased by 25
        $updatedBalance = \App\Models\StockBalance::where('component_id', $comp->id)
            ->where('location_id', $rak3->id)
            ->first();
        $this->assertNotNull($updatedBalance);
        $this->assertEquals($initialQty + 25, (float) $updatedBalance->quantity);
        $this->assertStringContainsString('1-R3-L3-05', $updatedBalance->specific_location_code);

        // Assert Transaction Inbound is recorded
        $this->assertDatabaseHas('transactions', [
            'type' => 'inbound',
            'work_request_id' => $wri->id,
            'reference_document' => $wri->wri_number,
        ]);
    }

    public function test_work_station_supply_reduces_warehouse_stock(): void
    {
        $comp = Component::first();
        $loc = Location::first();
        $ws = \App\Models\WorkStation::first();

        // Ensure at least 10 stock exists at this location
        $balance = \App\Models\StockBalance::firstOrCreate(
            ['component_id' => $comp->id, 'location_id' => $loc->id],
            ['quantity' => 20]
        );
        $balance->quantity = max(20, (float) $balance->quantity);
        $balance->save();

        $initialStock = (float) $balance->quantity;

        // Dispatch / Supply 5 units to work station
        $response = $this->actingAs($this->user)->post('/work-station-supplies', [
            'work_station_id' => $ws->id,
            'spk_number' => 'SPK-DT-TEST-001',
            'recipient_name' => 'Mandor Supriyadi',
            'transaction_date' => now()->toDateString(),
            'notes' => 'Supply perakitan unit karoseri dump',
            'items' => [
                [
                    'component_id' => $comp->id,
                    'from_location_id' => $loc->id,
                    'quantity' => 5,
                    'notes' => 'Mounting subframe',
                ],
            ],
        ]);

        $response->assertStatus(302);

        $balance->refresh();
        $this->assertEquals($initialStock - 5, (float) $balance->quantity);

        $this->assertDatabaseHas('transactions', [
            'type' => 'outbound',
            'work_station_id' => $ws->id,
            'spk_number' => 'SPK-DT-TEST-001',
            'recipient_name' => 'Mandor Supriyadi',
        ]);
    }

    public function test_fresh_install_empty_state_resilience_and_onboarding(): void
    {
        // Simulate a fresh database with ONLY the user account present
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \App\Models\StockBalance::truncate();
        \App\Models\TransactionItem::truncate();
        \App\Models\Transaction::truncate();
        \App\Models\WorkRequestStep::truncate();
        \App\Models\WorkRequest::truncate();
        \App\Models\Component::truncate();
        \App\Models\Location::truncate();
        \App\Models\Zone::truncate();
        \App\Models\Warehouse::truncate();
        \App\Models\Machine::truncate();
        \App\Models\WorkStation::truncate();
        \App\Models\Setting::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $this->assertEquals(0, Warehouse::count());
        $this->assertEquals(0, Location::count());
        $this->assertEquals(0, Component::count());
        $this->assertEquals(0, Machine::count());

        // 1. Warehouse Map on fresh install should NOT crash with 500 error, but auto-provision and render onboarding card
        $mapResponse = $this->actingAs($this->user)->get('/warehouse-map');
        $mapResponse->assertStatus(200);
        $mapResponse->assertSee('Denah Gedung Baru Siap Didesain');
        $mapResponse->assertSee('Gedung Utama HPK');

        $this->assertDatabaseHas('warehouses', ['code' => 'GDG-01', 'name' => 'Gedung Utama HPK']);
        $this->assertDatabaseHas('zones', ['code' => '1']);

        $warehouse = Warehouse::first();

        // 2. Adjust building dimensions from initial onboarding
        $updateAreaResponse = $this->actingAs($this->user)->postJson("/warehouse-map/warehouses/{$warehouse->id}/update-area", [
            'name' => 'Gedung Fabrikasi Karoseri A',
            'grid_columns' => 20,
            'grid_rows' => 14,
            'width_meters' => 28.0,
            'length_meters' => 40.0,
            'description' => 'Area workshop & gudang assembling karoseri',
        ]);
        $updateAreaResponse->assertStatus(200);
        $updateAreaResponse->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'Gedung Fabrikasi Karoseri A',
            'grid_columns' => 20,
            'grid_rows' => 14,
        ]);

        // 3. Add first rack on the grid
        $addRackResponse = $this->actingAs($this->user)->postJson('/warehouse-map/create-location', [
            'warehouse_id' => $warehouse->id,
            'storage_type' => 'rack',
            'grid_x' => 3,
            'grid_y' => 3,
            'grid_w' => 1,
            'grid_h' => 1,
            'levels' => 4,
            'slots_per_level' => 6,
        ]);
        $addRackResponse->assertStatus(200);
        $addRackResponse->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('locations', [
            'warehouse_id' => $warehouse->id,
            'storage_type' => 'rack',
            'rack_number' => 'Rak 1',
        ]);

        // 4. Add first pallet on the grid
        $addPalletResponse = $this->actingAs($this->user)->postJson('/warehouse-map/create-location', [
            'warehouse_id' => $warehouse->id,
            'storage_type' => 'pallet',
            'grid_x' => 5,
            'grid_y' => 5,
            'grid_w' => 2,
            'grid_h' => 1,
        ]);
        $addPalletResponse->assertStatus(200);
        $addPalletResponse->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('locations', [
            'warehouse_id' => $warehouse->id,
            'storage_type' => 'pallet',
            'rack_number' => 'Pallet 1',
        ]);

        // 5. Open Component creation form
        $compCreateResponse = $this->withoutExceptionHandling()->actingAs($this->user)->get('/components/create');
        $compCreateResponse->assertStatus(200);
        $compCreateResponse->assertSee('Rak 1');

        // 6. Open Work Requests (should auto-provision 5 machines)
        $wriResponse = $this->actingAs($this->user)->get('/work-requests');
        $wriResponse->assertStatus(200);
        $this->assertDatabaseHas('machines', ['code' => 'MC-LC-01']);
        $this->assertDatabaseHas('machines', ['code' => 'MC-BND-01']);

        // 7. Open Work Station Supply (should auto-provision work stations)
        $supplyResponse = $this->actingAs($this->user)->get('/work-station-supplies/create');
        $supplyResponse->assertStatus(200);
        $this->assertDatabaseHas('work_stations', ['code' => 'WS-DT-01']);

        // 8. Open Admin Settings (should auto-provision settings)
        $settingsResponse = $this->actingAs($this->user)->get('/admin/settings');
        $settingsResponse->assertStatus(200);
        $this->assertDatabaseHas('settings', ['key' => 'app_name']);
    }
}
