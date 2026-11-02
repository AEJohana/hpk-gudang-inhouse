<?php

namespace Tests\Unit;

use App\Models\Component;
use App\Models\QrRequest;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QrRequestQueueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->string('category')->default('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('component_id');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('qr_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->unsignedBigInteger('component_id');
            $table->string('label_type')->default('item');
            $table->integer('print_qty')->default(1);
            $table->string('notes')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('requested_by');
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_default_status_resolution_falls_back_to_pending(): void
    {
        $statusParam = null;
        $currentStatus = in_array($statusParam, ['pending', 'printed', 'all'], true) ? $statusParam : 'pending';
        $this->assertEquals('pending', $currentStatus);

        $emptyStatus = '';
        $currentStatusEmpty = in_array($emptyStatus, ['pending', 'printed', 'all'], true) ? $emptyStatus : 'pending';
        $this->assertEquals('pending', $currentStatusEmpty);

        $allStatus = 'all';
        $currentStatusAll = in_array($allStatus, ['pending', 'printed', 'all'], true) ? $allStatus : 'pending';
        $this->assertEquals('all', $currentStatusAll);

        $printedStatus = 'printed';
        $currentStatusPrinted = in_array($printedStatus, ['pending', 'printed', 'all'], true) ? $printedStatus : 'pending';
        $this->assertEquals('printed', $currentStatusPrinted);
    }

    public function test_kpi_counts_aggregation_accuracy(): void
    {
        $user = User::create([
            'name' => 'Operator HPK',
            'email' => 'operator@hpk.test',
            'password' => bcrypt('password'),
        ]);

        $comp1 = Component::create([
            'part_number' => 'PART-A-001',
            'name' => 'Braket Depan',
        ]);

        $comp2 = Component::create([
            'part_number' => 'PART-B-002',
            'name' => 'Engsel Pintu',
        ]);

        // 3 Pending requests
        for ($i = 1; $i <= 3; $i++) {
            QrRequest::create([
                'request_number' => "QR-REQ-PENDING-{$i}",
                'component_id' => $comp1->id,
                'label_type' => 'item',
                'print_qty' => 5,
                'status' => 'pending',
                'requested_by' => $user->id,
            ]);
        }

        // 2 Printed requests
        for ($i = 1; $i <= 2; $i++) {
            QrRequest::create([
                'request_number' => "QR-REQ-PRINTED-{$i}",
                'component_id' => $comp2->id,
                'label_type' => 'box',
                'print_qty' => 2,
                'status' => 'printed',
                'requested_by' => $user->id,
                'printed_at' => now(),
            ]);
        }

        $counts = QrRequest::selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'printed' THEN 1 END) as printed
        ")->first();

        $this->assertEquals(5, $counts->total);
        $this->assertEquals(3, $counts->pending);
        $this->assertEquals(2, $counts->printed);
    }

    public function test_qr_request_status_filtering_and_search(): void
    {
        $user = User::create([
            'name' => 'Gudang HPK',
            'email' => 'gudang@hpk.test',
            'password' => bcrypt('password'),
        ]);

        $compA = Component::create([
            'part_number' => 'HNG-001',
            'name' => 'Engsel Box Truk',
        ]);

        $compB = Component::create([
            'part_number' => 'BRK-002',
            'name' => 'Braket Bumper Depan',
        ]);

        QrRequest::create([
            'request_number' => 'QR-REQ-202610-101',
            'component_id' => $compA->id,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);

        QrRequest::create([
            'request_number' => 'QR-REQ-202610-102',
            'component_id' => $compB->id,
            'status' => 'printed',
            'requested_by' => $user->id,
        ]);

        // Filter: Pending (Default)
        $pendingResults = QrRequest::where('status', 'pending')->get();
        $this->assertCount(1, $pendingResults);
        $this->assertEquals('QR-REQ-202610-101', $pendingResults->first()->request_number);

        // Filter: Printed
        $printedResults = QrRequest::where('status', 'printed')->get();
        $this->assertCount(1, $printedResults);
        $this->assertEquals('QR-REQ-202610-102', $printedResults->first()->request_number);

        // Filter: Search by component part number
        $searchPartQuery = QrRequest::where(function ($q) {
            $q->where('request_number', 'like', '%BRK-002%')
                ->orWhereHas('component', function ($cq) {
                    $cq->where('part_number', 'like', '%BRK-002%')
                        ->orWhere('name', 'like', '%BRK-002%');
                });
        })->get();

        $this->assertCount(1, $searchPartQuery);
        $this->assertEquals('QR-REQ-202610-102', $searchPartQuery->first()->request_number);
    }

    public function test_status_badge_attribute(): void
    {
        $pendingReq = new QrRequest(['status' => 'pending']);
        $this->assertEquals('Menunggu Cetak', $pendingReq->status_badge['label']);
        $this->assertStringContainsString('amber', $pendingReq->status_badge['class']);

        $printedReq = new QrRequest(['status' => 'printed']);
        $this->assertEquals('Sudah Dicetak', $printedReq->status_badge['label']);
        $this->assertStringContainsString('emerald', $printedReq->status_badge['class']);
    }

    public function test_component_serialization_for_sliding_preview(): void
    {
        $comp = Component::create([
            'part_number' => 'CSAA50310210000',
            'name' => 'AS DRAT',
            'category' => 'subframe',
        ]);

        $serialized = [
            'id' => (string) $comp->id,
            'part_number' => $comp->part_number,
            'name' => $comp->name,
            'category' => $comp->category_label,
            'uom' => $comp->uom ?: 'Pcs',
            'location' => $comp->default_full_location_code !== '-' 
                ? $comp->default_full_location_code 
                : ($comp->defaultLocation ? $comp->defaultLocation->full_location_code : 'Belum diatur'),
            'dimension' => $comp->dimension_formatted,
            'total_stock' => (float) $comp->total_stock,
            'image_url' => $comp->image_url,
            'is_pdf' => (bool) $comp->is_pdf_drawing,
            'specs' => $comp->description ?: '',
        ];

        $this->assertEquals('CSAA50310210000', $serialized['part_number']);
        $this->assertEquals('AS DRAT', $serialized['name']);
        $this->assertEquals('Subframe', $serialized['category']);
        $this->assertEquals('Belum diatur', $serialized['location']);
        $this->assertEquals('Pcs', $serialized['uom']);
        $this->assertNotEmpty($serialized['image_url']);
        $this->assertFalse($serialized['is_pdf']);

        // Non-existent part number should resolve to placeholder
        $dummyComp = Component::create([
            'part_number' => 'DUMMY-NO-PHOTO-XYZ-999',
            'name' => 'Dummy Part',
        ]);
        $this->assertStringContainsString('placeholder_part.svg', $dummyComp->image_url);
    }

    public function test_cart_multi_item_batch_creation_and_label_multipliers(): void
    {
        $user = User::create([
            'name' => 'Staf Cetak Label',
            'email' => 'staflabel@hpk.test',
            'password' => bcrypt('password'),
        ]);

        $compA = Component::create([
            'part_number' => 'PART-KERANJANG-001',
            'name' => 'Bracket Chassis',
        ]);

        $compB = Component::create([
            'part_number' => 'PART-KERANJANG-002',
            'name' => 'Pin Engsel',
        ]);

        // Simulasi cart dengan kedua jenis label untuk Part A, dan 1 label untuk Part B
        // Label QR Rak: 1 input = 1 label (qty = 1)
        // Label QR Kartu: 1 set = 10 label (qty = 1 set * 10 = 10)
        // Part B: Label QR Kartu 3 set = 30 label
        $cartItems = [
            [
                'component_id' => $compA->id,
                'label_type' => 'rack',
                'print_qty' => 1,
                'notes' => 'Label slot rak A-01',
            ],
            [
                'component_id' => $compA->id,
                'label_type' => 'card',
                'print_qty' => 10, // 1 set @ 10
                'notes' => 'Stiker tempel part',
            ],
            [
                'component_id' => $compB->id,
                'label_type' => 'card',
                'print_qty' => 30, // 3 set @ 10
                'notes' => 'Stiker part 30 pcs',
            ]
        ];

        $request = new \Illuminate\Http\Request([
            'cart_data' => json_encode($cartItems)
        ]);

        $this->actingAs($user);
        $controller = new \App\Http\Controllers\QrRequestController();
        $response = $controller->store($request);

        $this->assertTrue($response->isRedirection());
        $this->assertEquals(route('qr-requests.index'), $response->headers->get('Location'));

        // Harus menghasilkan 3 tiket terpisah di database
        $created = QrRequest::whereIn('component_id', [$compA->id, $compB->id])->get();
        $this->assertCount(3, $created);

        // Verifikasi tiket Rak Part A
        $ticketRackA = $created->where('component_id', $compA->id)->where('label_type', 'rack')->first();
        $this->assertNotNull($ticketRackA);
        $this->assertEquals(1, $ticketRackA->print_qty);
        $this->assertEquals('pending', $ticketRackA->status);
        $this->assertStringStartsWith('QR-REQ-', $ticketRackA->request_number);

        // Verifikasi tiket Kartu Part A (10 pcs)
        $ticketCardA = $created->where('component_id', $compA->id)->where('label_type', 'card')->first();
        $this->assertNotNull($ticketCardA);
        $this->assertEquals(10, $ticketCardA->print_qty);
        $this->assertEquals('pending', $ticketCardA->status);

        // Verifikasi tiket Kartu Part B (30 pcs)
        $ticketCardB = $created->where('component_id', $compB->id)->where('label_type', 'card')->first();
        $this->assertNotNull($ticketCardB);
        $this->assertEquals(30, $ticketCardB->print_qty);
        $this->assertEquals('pending', $ticketCardB->status);
    }

    public function test_bulk_print_marks_pending_tickets_as_printed_and_renders_sheet(): void
    {
        $user = User::create([
            'name' => 'Operator Bulk Print',
            'email' => 'bulkprint@hpk.test',
            'password' => bcrypt('password'),
        ]);

        $comp1 = Component::create([
            'part_number' => 'PART-BULK-001',
            'name' => 'Handle Pintu Luar',
        ]);

        $comp2 = Component::create([
            'part_number' => 'PART-BULK-002',
            'name' => 'Join Shaft Karoseri',
        ]);

        $req1 = QrRequest::create([
            'request_number' => 'QR-REQ-BULK-01',
            'component_id' => $comp1->id,
            'label_type' => 'rack',
            'print_qty' => 1,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);

        $req2 = QrRequest::create([
            'request_number' => 'QR-REQ-BULK-02',
            'component_id' => $comp2->id,
            'label_type' => 'card',
            'print_qty' => 2,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);

        $this->actingAs($user);
        $controller = new \App\Http\Controllers\QrRequestController();
        $request = new \Illuminate\Http\Request([
            'ids' => [$req1->id, $req2->id]
        ]);

        $viewResponse = $controller->bulkPrint($request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $viewResponse);
        $this->assertEquals('qr_requests.print_sheet', $viewResponse->name());
        $this->assertTrue($viewResponse->getData()['isBulk']);
        $this->assertCount(2, $viewResponse->getData()['qrRequests']);

        // Pastikan status di database berubah menjadi printed
        $req1->refresh();
        $req2->refresh();
        $this->assertEquals('printed', $req1->status);
        $this->assertNotNull($req1->printed_at);
        $this->assertEquals('printed', $req2->status);
        $this->assertNotNull($req2->printed_at);
    }

    public function test_bulk_print_supports_reprinting_already_printed_tickets(): void
    {
        $user = User::create([
            'name' => 'Operator Reprint',
            'email' => 'reprint@hpk.test',
            'password' => bcrypt('password'),
        ]);

        $comp = Component::create([
            'part_number' => 'PART-REPRINT-001',
            'name' => 'Plat Penahan',
        ]);

        $req = QrRequest::create([
            'request_number' => 'QR-REQ-REPRINT-01',
            'component_id' => $comp->id,
            'label_type' => 'rack',
            'print_qty' => 2,
            'status' => 'printed',
            'printed_at' => now()->subDay(),
            'requested_by' => $user->id,
        ]);

        $this->actingAs($user);
        $controller = new \App\Http\Controllers\QrRequestController();
        $request = new \Illuminate\Http\Request([
            'ids' => [$req->id]
        ]);

        $viewResponse = $controller->bulkPrint($request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $viewResponse);
        $this->assertEquals('qr_requests.print_sheet', $viewResponse->name());
        $this->assertEquals('printed', $req->fresh()->status);
    }
}
