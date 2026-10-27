<?php

namespace Tests\Unit;

use App\Models\Component;
use App\Models\Location;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Models\Zone;
use App\Services\ComponentBulkImportService;
use App\Services\ComponentExcelTemplateService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use Tests\TestCase;

class ComponentBulkImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Gudang Utama');
            $table->string('code')->default('WH-01');
            $table->integer('grid_columns')->default(16);
            $table->integer('grid_rows')->default(12);
            $table->decimal('width_meters', 8, 2)->default(32.0);
            $table->decimal('length_meters', 8, 2)->default(24.0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('name')->default('Zona A');
            $table->string('code')->default('1');
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('zone_id')->nullable();
            $table->string('zone_code')->nullable();
            $table->string('zone_name')->nullable();
            $table->string('aisle')->nullable();
            $table->string('rack_number')->nullable();
            $table->string('storage_type')->default('rack');
            $table->string('rack_code')->nullable();
            $table->string('bin_level')->nullable();
            $table->integer('total_levels')->default(4);
            $table->integer('slots_per_level')->default(6);
            $table->text('description')->nullable();
            $table->integer('max_capacity')->default(100);
            $table->integer('grid_x')->default(1);
            $table->integer('grid_y')->default(1);
            $table->integer('grid_w')->default(1);
            $table->integer('grid_h')->default(1);
            $table->string('color')->nullable();
            $table->text('level_slots_config')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('uom')->default('PCS');
            $table->decimal('length_mm', 10, 2)->nullable();
            $table->decimal('width_or_diameter_mm', 10, 2)->nullable();
            $table->decimal('thickness_or_height_mm', 10, 2)->nullable();
            $table->text('specification')->nullable();
            $table->integer('minimum_stock')->default(0);
            $table->integer('maximum_stock')->nullable();
            $table->foreignId('default_location_id')->nullable();
            $table->string('image_path')->nullable();
            $table->text('qr_code_payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('process_route')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id');
            $table->foreignId('location_id');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('batch_lot_number')->nullable();
            $table->integer('bin_level')->nullable();
            $table->string('slot_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Test template generator creates a valid 2-sheet XLSX file.
     */
    public function test_template_generator_creates_valid_two_sheet_xlsx(): void
    {
        $service = new ComponentExcelTemplateService();
        $filePath = tempnam(sys_get_temp_dir(), 'hpk_tpl_') . '.xlsx';
        $service->generateTemplate($filePath);

        $this->assertFileExists($filePath);
        $this->assertGreaterThan(1000, filesize($filePath), 'Template XLSX should not be empty');

        $reader = new XLSXReader();
        $reader->open($filePath);

        $sheetNames = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetNames[] = $sheet->getName();
        }
        $reader->close();

        $this->assertCount(2, $sheetNames, 'Template should contain exactly 2 sheets');
        $this->assertTrue(str_contains($sheetNames[0], 'INPUT'), 'Sheet 1 must be INPUT DATA KOMPONEN');
        $this->assertTrue(str_contains($sheetNames[1], 'PEDOMAN'), 'Sheet 2 must be PEDOMAN');

        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    /**
     * Test bulk import service parses CSV rows and handles duplicates in skip mode.
     */
    public function test_bulk_import_service_parses_csv_with_skip_duplicate(): void
    {
        // Seed an existing component
        Component::create([
            'part_number' => 'COMP-EXIST',
            'name' => 'Existing Plate 5mm',
            'category' => 'subframe',
            'uom' => 'PCS',
        ]);

        $csvContent = implode("\n", [
            'Kode Komponen (*Wajib),Nama Komponen (*Wajib),Kategori,Satuan,Panjang (mm),Lebar/Diameter (mm),Tebal/Tinggi (mm),Spesifikasi Teknis,Stok Minimum,Stok Awal,Kode Rak,Tautan Foto',
            'COMP-NEW-01,Plat Baru 10mm,subframe,PCS,200,100,10,SS400 High Grade,5,10,,',
            ',Part Tanpa Kode,subframe,PCS,,,,,,,,,', // Missing part_number -> Error
            'COMP-NO-NAME,,subframe,PCS,,,,,,,,,',     // Missing name -> Error
            'COMP-NEW-01,Plat Duplikat di File,subframe,PCS,,,,,,,,,', // Intra-file duplicate -> Error
            'COMP-EXIST,Plat Yang Sudah Ada,subframe,PCS,200,100,5,Existing Spec,10,20,,', // DB duplicate -> skip
        ]);

        $tempCsv = tempnam(sys_get_temp_dir(), 'test_import_') . '.csv';
        file_put_contents($tempCsv, $csvContent);

        $service = new ComponentBulkImportService();
        $preview = $service->parseAndPreview($tempCsv, 'csv', 'skip');

        $this->assertArrayHasKey('summary', $preview);
        $this->assertEquals(5, $preview['summary']['total_rows']);
        $this->assertEquals(1, $preview['summary']['new_items'], 'Only COMP-NEW-01 is new');
        $this->assertEquals(3, $preview['summary']['errors'], '3 rows have validation/duplicate errors');
        $this->assertEquals(1, $preview['summary']['duplicate_skip'], 'COMP-EXIST should be marked skip');
        $this->assertEquals(0, $preview['summary']['duplicate_update']);

        @unlink($tempCsv);
    }

    /**
     * Test bulk import service marks duplicate as duplicate_update when duplicate_action is 'update'.
     */
    public function test_bulk_import_service_parses_csv_with_update_duplicate(): void
    {
        Component::create([
            'part_number' => 'COMP-UPDATE-ME',
            'name' => 'Old Name Component',
            'category' => 'subframe',
            'uom' => 'PCS',
        ]);

        $csvContent = implode("\n", [
            'Kode Komponen (*Wajib),Nama Komponen (*Wajib),Kategori,Satuan,Panjang (mm),Lebar/Diameter (mm),Tebal/Tinggi (mm),Spesifikasi Teknis,Stok Minimum,Stok Awal,Kode Rak,Tautan Foto',
            'COMP-UPDATE-ME,Updated Name Component,subframe,SET,500,250,12,Updated Spec,15,50,,',
        ]);

        $tempCsv = tempnam(sys_get_temp_dir(), 'test_import_') . '.csv';
        file_put_contents($tempCsv, $csvContent);

        $service = new ComponentBulkImportService();
        $preview = $service->parseAndPreview($tempCsv, 'csv', 'update');

        $this->assertEquals(1, $preview['summary']['total_rows']);
        $this->assertEquals(0, $preview['summary']['new_items']);
        $this->assertEquals(1, $preview['summary']['duplicate_update'], 'Should be marked as duplicate_update');
        $this->assertEquals(0, $preview['summary']['duplicate_skip']);
        $this->assertEquals(0, $preview['summary']['errors']);

        @unlink($tempCsv);
    }

    /**
     * Test header detection correctly ignores introductory titles and notes containing "kode".
     */
    public function test_header_detection_ignores_introductory_titles_and_instruction_notes(): void
    {
        $csvContent = implode("\n", [
            'PT HYDRAXLE PERKASA - TEMPLATE IMPORT MASTER KOMPONEN KAROSERI',
            'Petunjuk: Kolom bertanda (*) WAJIB diisi. Lihat Sheet PEDOMAN untuk daftar kategori satuan dan kode rak aktif.',
            '',
            'Kode Komponen (*Wajib),Nama Komponen (*Wajib),Kategori,Satuan,Panjang (mm),Lebar/Diameter (mm),Tebal/Tinggi (mm),Spesifikasi Teknis,Stok Minimum,Stok Awal,Kode Rak,Tautan Foto',
            '000000000000,UJI COBA KOMPONE,subframe,BATANG,6000,100,5,Material Baja UNP,0,50,A-01,',
        ]);

        $tempCsv = tempnam(sys_get_temp_dir(), 'test_header_') . '.csv';
        file_put_contents($tempCsv, $csvContent);

        $service = new ComponentBulkImportService();
        $preview = $service->parseAndPreview($tempCsv, 'csv', 'skip');

        $this->assertEquals(1, $preview['summary']['total_rows'], 'Only 1 actual component row must be parsed, header should not be treated as data');
        $this->assertEquals(1, $preview['summary']['new_items']);
        $this->assertEquals(0, $preview['summary']['errors']);
        $this->assertEquals('000000000000', $preview['rows'][0]['part_number']);
        $this->assertEquals('UJI COBA KOMPONE', $preview['rows'][0]['name']);
        $this->assertEquals('new', $preview['rows'][0]['status']);

        @unlink($tempCsv);
    }

    /**
     * Test fail-safe guard automatically skips lingering header rows duplicated in data.
     */
    public function test_fail_safe_ignores_lingering_duplicate_headers_in_data(): void
    {
        $csvContent = implode("\n", [
            'Kode Komponen (*Wajib),Nama Komponen (*Wajib),Kategori,Satuan,Panjang (mm),Lebar/Diameter (mm),Tebal/Tinggi (mm),Spesifikasi Teknis,Stok Minimum,Stok Awal,Kode Rak,Tautan Foto',
            'PART-REAL-01,Part Asli Karoseri,subframe,PCS,100,50,3,Spek Asli,5,10,,',
            'Kode Komponen (*Wajib),Nama Komponen (*Wajib),Kategori,Satuan,Panjang (mm),Lebar/Diameter (mm),Tebal/Tinggi (mm),Spesifikasi Teknis,Stok Minimum,Stok Awal,Kode Rak,Tautan Foto',
        ]);

        $tempCsv = tempnam(sys_get_temp_dir(), 'test_failsafe_') . '.csv';
        file_put_contents($tempCsv, $csvContent);

        $service = new ComponentBulkImportService();
        $preview = $service->parseAndPreview($tempCsv, 'csv', 'skip');

        $this->assertEquals(1, $preview['summary']['total_rows'], 'Lingering duplicate header in data must be skipped by fail-safe');
        $this->assertEquals(1, $preview['summary']['new_items']);
        $this->assertEquals('PART-REAL-01', $preview['rows'][0]['part_number']);

        @unlink($tempCsv);
    }

    /**
     * Test execute import persists components and updates them in database.
     */
    public function test_bulk_import_service_execute_persists_to_database(): void
    {
        Component::create([
            'part_number' => 'EXIST-001',
            'name' => 'Original Component Name',
            'category' => 'subframe',
            'uom' => 'PCS',
        ]);

        $cacheToken = 'test_token_' . uniqid();
        $rows = [
            [
                'row_number' => 1,
                'part_number' => 'NEW-IMPORT-99',
                'name' => 'Baru Ditambahkan via Excel',
                'category' => 'assy',
                'uom' => 'SET',
                'length_mm' => 120,
                'width_or_diameter_mm' => 80,
                'thickness_or_height_mm' => 5,
                'specification' => 'Material ASTM A36',
                'minimum_stock' => 10,
                'initial_stock' => 25,
                'location_id' => null,
                'image_url' => null,
                'status' => 'new',
            ],
            [
                'row_number' => 2,
                'part_number' => 'EXIST-001',
                'name' => 'Renamed Component Name',
                'category' => 'subframe',
                'uom' => 'BOX',
                'length_mm' => null,
                'width_or_diameter_mm' => null,
                'thickness_or_height_mm' => null,
                'specification' => 'Updated Spec Note',
                'minimum_stock' => 5,
                'initial_stock' => 0,
                'location_id' => null,
                'image_url' => null,
                'status' => 'duplicate_update',
            ],
            [
                'row_number' => 3,
                'part_number' => 'INVALID-01',
                'name' => '',
                'category' => null,
                'uom' => 'PCS',
                'length_mm' => null,
                'width_or_diameter_mm' => null,
                'thickness_or_height_mm' => null,
                'specification' => null,
                'minimum_stock' => 0,
                'initial_stock' => 0,
                'location_id' => null,
                'image_url' => null,
                'status' => 'error',
            ],
        ];

        Cache::put($cacheToken, [
            'rows' => $rows,
            'duplicate_action' => 'update',
        ], now()->addMinutes(30));

        $service = new ComponentBulkImportService();
        $result = $service->confirmAndStore($cacheToken, 'update');

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['inserted_count']);
        $this->assertEquals(1, $result['updated_count']);
        $this->assertEquals(0, $result['skipped_count']);

        // Assert NEW-IMPORT-99 was created in database
        $newComp = Component::where('part_number', 'NEW-IMPORT-99')->first();
        $this->assertNotNull($newComp);
        $this->assertEquals('Baru Ditambahkan via Excel', $newComp->name);
        $this->assertEquals('assy', $newComp->category);
        $this->assertEquals('SET', $newComp->uom);

        // Assert EXIST-001 was updated in database
        $updatedComp = Component::where('part_number', 'EXIST-001')->first();
        $this->assertNotNull($updatedComp);
        $this->assertEquals('Renamed Component Name', $updatedComp->name);
        $this->assertEquals('BOX', $updatedComp->uom);

        // Assert INVALID-01 was NOT created in database
        $this->assertNull(Component::where('part_number', 'INVALID-01')->first());
    }
}
