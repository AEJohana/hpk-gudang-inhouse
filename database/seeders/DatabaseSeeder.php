<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Location;
use App\Models\Component;
use App\Models\StockBalance;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Ecr;
use App\Models\Disposal;
use App\Models\DisposalItem;
use App\Models\QrRequest;
use App\Models\CycleCount;
use App\Models\CycleCountItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Users
        $admin = User::updateOrCreate(
            ['email' => 'admin@hydraxle.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'role' => 'admin_gudang',
                'department' => 'Gudang & Logistik',
                'phone' => '0812-3456-7890',
            ]
        );

        $spv = User::updateOrCreate(
            ['email' => 'spv.gudang@hydraxle.com'],
            [
                'name' => 'Agus Wijaya',
                'password' => Hash::make('password'),
                'role' => 'supervisor',
                'department' => 'Kepala Gudang',
                'phone' => '0812-3456-7891',
            ]
        );

        $operator = User::updateOrCreate(
            ['email' => 'operator@hydraxle.com'],
            [
                'name' => 'Rahmat Hidayat',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'department' => 'Gudang Lapangan',
                'phone' => '0812-3456-7892',
            ]
        );

        $engineer = User::updateOrCreate(
            ['email' => 'engineer@hydraxle.com'],
            [
                'name' => 'Dedi Pratama',
                'password' => Hash::make('password'),
                'role' => 'engineering',
                'department' => 'Engineering Karoseri',
                'phone' => '0812-3456-7893',
            ]
        );

        $qc = User::updateOrCreate(
            ['email' => 'qc@hydraxle.com'],
            [
                'name' => 'Hendra Kusuma',
                'password' => Hash::make('password'),
                'role' => 'qc',
                'department' => 'Quality Control',
                'phone' => '0812-3456-7894',
            ]
        );

        // 2. Create Warehouse Locations (Single-building HPK Warehouse)
        $locationsData = [
            // Zona A: Raw Material Baja
            ['zone_code' => 'A', 'zone_name' => 'Raw Material Baja', 'aisle' => 'Lorong A1', 'rack_number' => 'BAY-01', 'bin_level' => 'Floor', 'description' => 'Area Stacking Pelat Baja Tebal (Under Crane)', 'max_capacity' => 80],
            ['zone_code' => 'A', 'zone_name' => 'Raw Material Baja', 'aisle' => 'Lorong A2', 'rack_number' => 'CANT-01', 'bin_level' => 'Level 1', 'description' => 'Rak Cantilever UNP & WF 6 Meter', 'max_capacity' => 120],
            
            // Zona B: Komponen Hidrolik & Presisi
            ['zone_code' => 'B', 'zone_name' => 'Komponen Hidrolik & Presisi', 'aisle' => 'Lorong B1', 'rack_number' => 'RK-HYD-01', 'bin_level' => 'L1', 'description' => 'Pallet Rack Silinder Hidrolik Telescopic', 'max_capacity' => 40],
            ['zone_code' => 'B', 'zone_name' => 'Komponen Hidrolik & Presisi', 'aisle' => 'Lorong B1', 'rack_number' => 'RK-HYD-02', 'bin_level' => 'L2', 'description' => 'Rak Heavy Duty Pompa & Control Valve', 'max_capacity' => 50],
            ['zone_code' => 'B', 'zone_name' => 'Komponen Hidrolik & Presisi', 'aisle' => 'Lorong B2', 'rack_number' => 'RK-PTO-01', 'bin_level' => 'L1', 'description' => 'Rak PTO Transmisi Berbagai Merk Truk', 'max_capacity' => 60],

            // Zona C: Hardware, Fastener & Aksesoris Karoseri
            ['zone_code' => 'C', 'zone_name' => 'Hardware & Fastener', 'aisle' => 'Lorong C1', 'rack_number' => 'BIN-FST-01', 'bin_level' => 'Bin 01', 'description' => 'Multi-tier Bins Baut High Tensile & Mur', 'max_capacity' => 200],
            ['zone_code' => 'C', 'zone_name' => 'Hardware & Fastener', 'aisle' => 'Lorong C2', 'rack_number' => 'RK-ACC-01', 'bin_level' => 'L1', 'description' => 'Rak Engsel Pintu Dump & Twist Lock', 'max_capacity' => 80],
            ['zone_code' => 'C', 'zone_name' => 'Hardware & Fastener', 'aisle' => 'Lorong C3', 'rack_number' => 'RK-ELC-01', 'bin_level' => 'L2', 'description' => 'Rak Komponen Lampu LED & Wiring Harness', 'max_capacity' => 70],

            // Zona D: Chemical & Cat
            ['zone_code' => 'D', 'zone_name' => 'Chemical & Cat', 'aisle' => 'Lorong D1', 'rack_number' => 'RK-CHM-01', 'bin_level' => 'Floor', 'description' => 'Ruang Berventilasi Khusus Drum Cat PU & Thinner', 'max_capacity' => 50],

            // Zona E: Staging / Buffer Lini Perakitan
            ['zone_code' => 'E', 'zone_name' => 'Staging Perakitan Karoseri', 'aisle' => 'Buffer 01', 'rack_number' => 'STG-01', 'bin_level' => 'Floor', 'description' => 'Buffer Material Siap Masuk Assembly Karoseri', 'max_capacity' => 60],

            // Zona F: Karantina & Scrap Yard
            ['zone_code' => 'F', 'zone_name' => 'Karantina & Scrap Yard', 'aisle' => 'Area Belakang', 'rack_number' => 'SCRAP-01', 'bin_level' => 'Yard', 'description' => 'Penampungan Afkir & Potongan Pelat Besi Tua', 'max_capacity' => 150],
        ];

        $locations = [];
        foreach ($locationsData as $data) {
            $locations[$data['rack_number']] = Location::create($data);
        }

        // 3. Components Data
        $componentsData = [
            [
                'part_number' => 'HYD-CYL-160',
                'name' => 'Silinder Hidrolik Telescopic 5-Stage 160mm',
                'category' => 'hydraulic',
                'uom' => 'Pcs',
                'specification' => 'Pin-to-pin 1450mm, Stroke 3800mm, Max Pressure 190 Bar, Dump Truck 24m3',
                'minimum_stock' => 4,
                'maximum_stock' => 20,
                'default_location_id' => $locations['RK-HYD-01']->id,
                'qr_code_payload' => 'HPK-PART|HYD-CYL-160',
                'stock_qty' => 3, // LOW STOCK ALERT
            ],
            [
                'part_number' => 'HYD-PMP-082',
                'name' => 'Hydraulic Gear Pump 82L Bi-Rotational',
                'category' => 'hydraulic',
                'uom' => 'Pcs',
                'specification' => 'Displacement 82cc/rev, Flange 4-Bolt ISO, Max 250 Bar, Shaft Spline DIN 5462',
                'minimum_stock' => 5,
                'maximum_stock' => 25,
                'default_location_id' => $locations['RK-HYD-02']->id,
                'qr_code_payload' => 'HPK-PART|HYD-PMP-082',
                'stock_qty' => 12,
            ],
            [
                'part_number' => 'HYD-PTO-HIN',
                'name' => 'Power Take-Off (PTO) Transmission Hino FM260',
                'category' => 'hydraulic',
                'uom' => 'Pcs',
                'specification' => 'Pneumatic Control, Gear Ratio 1:1.32, Output ISO 4-Bolt, Heavy Duty',
                'minimum_stock' => 4,
                'maximum_stock' => 15,
                'default_location_id' => $locations['RK-PTO-01']->id,
                'qr_code_payload' => 'HPK-PART|HYD-PTO-HIN',
                'stock_qty' => 7,
            ],
            [
                'part_number' => 'RAW-PLT-006',
                'name' => 'Pelat Baja Hardox 450 Tebal 6mm (Floor Plate)',
                'category' => 'raw_material',
                'uom' => 'Lembar',
                'specification' => 'Ukuran 1500 x 6000mm, Hardness 450 HBW, Abrasion Resistant untuk Lantai Dump',
                'minimum_stock' => 10,
                'maximum_stock' => 50,
                'default_location_id' => $locations['BAY-01']->id,
                'qr_code_payload' => 'HPK-PART|RAW-PLT-006',
                'stock_qty' => 8, // LOW STOCK ALERT
            ],
            [
                'part_number' => 'RAW-UNP-150',
                'name' => 'Baja Profil Kanal UNP 150 x 75 x 6.5mm (6m)',
                'category' => 'raw_material',
                'uom' => 'Batang (6m)',
                'specification' => 'Standar JIS G3101 SS400, Panjang 6 Meter, Cross Member & Subframe Karoseri',
                'minimum_stock' => 15,
                'maximum_stock' => 80,
                'default_location_id' => $locations['CANT-01']->id,
                'qr_code_payload' => 'HPK-PART|RAW-UNP-150',
                'stock_qty' => 42,
            ],
            [
                'part_number' => 'FST-BLT-M16',
                'name' => 'Baut High Tensile Hex Grade 8.8 M16 x 50mm + Nut',
                'category' => 'fastener',
                'uom' => 'Pcs',
                'specification' => 'Grade 8.8 Black Finish, Pitch 2.0, Tensile Strength 800 MPa, DIN 933',
                'minimum_stock' => 200,
                'maximum_stock' => 1500,
                'default_location_id' => $locations['BIN-FST-01']->id,
                'qr_code_payload' => 'HPK-PART|FST-BLT-M16',
                'stock_qty' => 650,
            ],
            [
                'part_number' => 'ACC-HNG-DMP',
                'name' => 'Heavy Duty Dump Tailgate Hinge Assembly Set',
                'category' => 'accessories',
                'uom' => 'Set',
                'specification' => 'Cast Steel Pin 40mm, Bushing Bronze, Greasable Nipple, Kapasitas 30 Ton',
                'minimum_stock' => 6,
                'maximum_stock' => 30,
                'default_location_id' => $locations['RK-ACC-01']->id,
                'qr_code_payload' => 'HPK-PART|ACC-HNG-DMP',
                'stock_qty' => 14,
            ],
            [
                'part_number' => 'ELC-LGT-24V',
                'name' => 'Lampu Belakang LED Karoseri 24V (Stop/Turn/Rev)',
                'category' => 'electrical',
                'uom' => 'Set',
                'specification' => 'IP67 Waterproof, Polycarbonate Lens, Voltage 24V DC, Standar E-Mark Karoseri',
                'minimum_stock' => 10,
                'maximum_stock' => 50,
                'default_location_id' => $locations['RK-ELC-01']->id,
                'qr_code_payload' => 'HPK-PART|ELC-LGT-24V',
                'stock_qty' => 22,
            ],
            [
                'part_number' => 'CHM-CAT-PUE',
                'name' => 'Topcoat Polyurethane HPK Dark Grey (20 Liter)',
                'category' => 'chemical_paint',
                'uom' => 'Liter',
                'specification' => 'Dua komponen (PU + Hardener), Ketahanan Korosi Tinggi & Tahan Sinar UV',
                'minimum_stock' => 40,
                'maximum_stock' => 200,
                'default_location_id' => $locations['RK-CHM-01']->id,
                'qr_code_payload' => 'HPK-PART|CHM-CAT-PUE',
                'stock_qty' => 80,
            ],
        ];

        $comps = [];
        foreach ($componentsData as $c) {
            $stockQty = $c['stock_qty'];
            unset($c['stock_qty']);

            $component = Component::create($c);
            $comps[$component->part_number] = $component;

            // Create initial stock balance
            StockBalance::create([
                'component_id' => $component->id,
                'location_id' => $component->default_location_id,
                'quantity' => $stockQty,
                'batch_lot_number' => 'LOT-2026-A1',
            ]);
        }

        // 4. Sample Recent Transactions
        $txIn = Transaction::create([
            'transaction_number' => 'TRX-IN-202610-001',
            'type' => 'inbound',
            'spk_number' => null, // opsional
            'reference_document' => 'PO-HYD-9981 / SJ-88219',
            'notes' => 'Penerimaan silinder hidrolik & pompa dari supplier import',
            'user_id' => $admin->id,
            'transaction_date' => now()->subDays(1),
            'status' => 'completed',
        ]);

        TransactionItem::create([
            'transaction_id' => $txIn->id,
            'component_id' => $comps['HYD-CYL-160']->id,
            'from_location_id' => null,
            'to_location_id' => $locations['RK-HYD-01']->id,
            'quantity' => 2,
            'unit_price' => 18500000,
            'notes' => 'Lolos uji inspeksi QC',
        ]);

        $txOut = Transaction::create([
            'transaction_number' => 'TRX-OUT-202610-001',
            'type' => 'outbound',
            'spk_number' => 'SPK-2026-DT-042', // Opsional, diisi contoh
            'reference_document' => 'WO-FAB-102',
            'notes' => 'Pengeluaran pelat Hardox & profil UNP untuk lini fabrikasi dump',
            'user_id' => $operator->id,
            'transaction_date' => now(),
            'status' => 'completed',
        ]);

        TransactionItem::create([
            'transaction_id' => $txOut->id,
            'component_id' => $comps['RAW-PLT-006']->id,
            'from_location_id' => $locations['BAY-01']->id,
            'to_location_id' => $locations['STG-01']->id,
            'quantity' => 2,
            'unit_price' => 12500000,
            'notes' => 'Diterima oleh Leader Fabrikasi Line 1',
        ]);

        // 5. Sample ECR (Engineering Change Request)
        Ecr::create([
            'ecr_number' => 'ECR-2026-10-001',
            'component_id' => $comps['HYD-CYL-160']->id,
            'title' => 'Peningkatan Grade Seal Kit Silinder Telescopic Menjadi Viton High Temp',
            'revision_type' => 'spec_change',
            'old_specification' => 'Standard NBR Seal Kit max 80 Celcius',
            'new_specification' => 'Viton High Temperature Seal Kit max 150 Celcius untuk operasi tambang batubara berat',
            'reason' => 'Menghindari resiko rembesan oli hidrolik pada operasional dump tambang bersuhu tinggi dan debu ekstrem.',
            'stock_policy' => 'run_out',
            'status' => 'submitted', // Menunggu persetujuan
            'requested_by' => $engineer->id,
        ]);

        // 6. Sample Disposal Request
        $disposal = Disposal::create([
            'disposal_number' => 'DSP-2026-10-001',
            'disposal_type' => 'scrap_iron',
            'reason' => 'Sisa potongan (offcut) pelat Hardox & profil baja hasil pemotongan plasma cutting yang tidak dapat dipakai lagi.',
            'estimated_weight_kg' => 480.00,
            'estimated_salvage_value' => 2880000, // Taksiran besi scrap Rp 6.000/kg
            'status' => 'submitted',
            'requested_by' => $operator->id,
        ]);

        DisposalItem::create([
            'disposal_id' => $disposal->id,
            'component_id' => $comps['RAW-PLT-006']->id,
            'from_location_id' => $locations['SCRAP-01']->id,
            'quantity' => 12,
            'condition_description' => 'Potongan pelat tidak beraturan sisa potong bevel',
        ]);

        // 7. Sample QR Label Request
        QrRequest::create([
            'request_number' => 'QR-REQ-202610-001',
            'component_id' => $comps['HYD-CYL-160']->id,
            'label_type' => 'item',
            'print_qty' => 5,
            'notes' => 'Label stiker thermal untuk unit silinder baru tiba',
            'status' => 'pending',
            'requested_by' => $admin->id,
        ]);

        // 8. Sample Cycle Count
        $count = CycleCount::create([
            'count_number' => 'CC-202610-001',
            'zone_target' => 'B',
            'count_date' => now(),
            'notes' => 'Stok Opname Rutin Mingguan Zona B (Komponen Hidrolik)',
            'status' => 'pending_review',
            'conducted_by' => $operator->id,
        ]);

        CycleCountItem::create([
            'cycle_count_id' => $count->id,
            'component_id' => $comps['HYD-CYL-160']->id,
            'location_id' => $locations['RK-HYD-01']->id,
            'system_qty' => 3,
            'physical_qty' => 3,
            'variance_qty' => 0,
            'variance_reason' => 'Sesuai fisik',
        ]);

        CycleCountItem::create([
            'cycle_count_id' => $count->id,
            'component_id' => $comps['HYD-PMP-082']->id,
            'location_id' => $locations['RK-HYD-02']->id,
            'system_qty' => 12,
            'physical_qty' => 11,
            'variance_qty' => -1,
            'variance_reason' => '1 unit sedang diuji di test bench hidrolik belum dicatat bon sementara',
        ]);
    }
}
