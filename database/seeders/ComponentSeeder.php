<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\ComponentCategory;
use App\Models\Location;
use App\Models\StockBalance;
use App\Models\Uom;
use Illuminate\Database\Seeder;

class ComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ComponentCategory::all()->keyBy('code');
        $uoms = Uom::all()->keyBy('code');
        $locations = Location::all()->keyBy('rack_number');

        $componentsData = [
            // RAK 3 (AREA 1 - CHASSIS & MOUNTING KAROSERI HINO)
            [
                'part_number' => 'CSFP10300250081',
                'name' => 'MOUNTING HINO 2',
                'category' => 'accessories',
                'uom' => 'Pcs',
                'specification' => 'Mounting Chassis & Cabin Hino FM260/Dutro Karoseri Heavy Duty (Slot 1-R3-L3-05)',
                'minimum_stock' => 5,
                'maximum_stock' => 50,
                'rack_number' => 'Rak 3',
                'shelf_level' => 'L3',
                'slot_number' => '05',
                'specific_location_code' => '1-R3-L3-05',
                'qr_code_payload' => 'HPK-PART|CSFP10300250081',
                'stock_qty' => 24,
                'batch_lot_number' => 'LOT-2026-HIN',
            ],
            [
                'part_number' => 'CSFP10300250082',
                'name' => 'BRACKET MOUNTING DEPAN HINO',
                'category' => 'accessories',
                'uom' => 'Pcs',
                'specification' => 'Bracket Dudukan Depan Karoseri Dump Truck Hino 500 Series',
                'minimum_stock' => 4,
                'maximum_stock' => 40,
                'rack_number' => 'Rak 3',
                'shelf_level' => 'L3',
                'slot_number' => '02',
                'specific_location_code' => '1-R3-L3-02',
                'qr_code_payload' => 'HPK-PART|CSFP10300250082',
                'stock_qty' => 18,
                'batch_lot_number' => 'LOT-2026-HIN',
            ],
            [
                'part_number' => 'ACC-UBT-M20',
                'name' => 'Baut U-Bolt Chassis M20 x 350mm + Double Nut',
                'category' => 'accessories',
                'uom' => 'Set',
                'specification' => 'High Tensile Grade 8.8, Pelindung Ulir, Pengikat Bodi Karoseri ke Sasis Truk',
                'minimum_stock' => 10,
                'maximum_stock' => 80,
                'rack_number' => 'Rak 3',
                'shelf_level' => 'L2',
                'slot_number' => '01',
                'specific_location_code' => '1-R3-L2-01',
                'qr_code_payload' => 'HPK-PART|ACC-UBT-M20',
                'stock_qty' => 40,
                'batch_lot_number' => 'LOT-2026-UBT',
            ],
            [
                'part_number' => 'ACC-TRN-HIN',
                'name' => 'Trunnion Bushing Set Hino FM Rear Suspension',
                'category' => 'accessories',
                'uom' => 'Set',
                'specification' => 'Bronze Bushing Heavy Duty, Oil Seal & Wear Plate Karoseri Dump',
                'minimum_stock' => 4,
                'maximum_stock' => 25,
                'rack_number' => 'Rak 3',
                'shelf_level' => 'L1',
                'slot_number' => '04',
                'specific_location_code' => '1-R3-L1-04',
                'qr_code_payload' => 'HPK-PART|ACC-TRN-HIN',
                'stock_qty' => 12,
                'batch_lot_number' => 'LOT-2026-TRN',
            ],

            // RAK 5 (AREA 1 - KOMPONEN HIDROLIK & PTO)
            [
                'part_number' => 'HYD-CYL-160',
                'name' => 'Silinder Hidrolik Telescopic 5-Stage 160mm',
                'category' => 'hydraulic',
                'uom' => 'Pcs',
                'specification' => 'Pin-to-pin 1450mm, Stroke 3800mm, Max Pressure 190 Bar, Dump Truck 24m3',
                'minimum_stock' => 4,
                'maximum_stock' => 20,
                'rack_number' => 'Rak 5',
                'shelf_level' => 'L1',
                'slot_number' => '01',
                'specific_location_code' => '1-R5-L1-01',
                'qr_code_payload' => 'HPK-PART|HYD-CYL-160',
                'stock_qty' => 4,
                'batch_lot_number' => 'LOT-2026-HYD',
            ],
            [
                'part_number' => 'HYD-PMP-082',
                'name' => 'Hydraulic Gear Pump 82L Bi-Rotational',
                'category' => 'hydraulic',
                'uom' => 'Pcs',
                'specification' => 'Displacement 82cc/rev, Flange 4-Bolt ISO, Max 250 Bar, Shaft Spline DIN 5462',
                'minimum_stock' => 5,
                'maximum_stock' => 25,
                'rack_number' => 'Rak 5',
                'shelf_level' => 'L2',
                'slot_number' => '03',
                'specific_location_code' => '1-R5-L2-03',
                'qr_code_payload' => 'HPK-PART|HYD-PMP-082',
                'stock_qty' => 12,
                'batch_lot_number' => 'LOT-2026-HYD',
            ],
            [
                'part_number' => 'HYD-PTO-HIN',
                'name' => 'Power Take-Off (PTO) Transmission Hino FM260',
                'category' => 'hydraulic',
                'uom' => 'Pcs',
                'specification' => 'Pneumatic Control, Gear Ratio 1:1.32, Output ISO 4-Bolt, Heavy Duty',
                'minimum_stock' => 4,
                'maximum_stock' => 15,
                'rack_number' => 'Rak 5',
                'shelf_level' => 'L3',
                'slot_number' => '02',
                'specific_location_code' => '1-R5-L3-02',
                'qr_code_payload' => 'HPK-PART|HYD-PTO-HIN',
                'stock_qty' => 7,
                'batch_lot_number' => 'LOT-2026-PTO',
            ],
            [
                'part_number' => 'HYD-VLV-001',
                'name' => 'Pneumatic Tipping Control Valve Karoseri',
                'category' => 'hydraulic',
                'uom' => 'Pcs',
                'specification' => 'Cabin Control Pneumatic Lever, Proportional Lowering, Pressure Relief Valve 190 Bar',
                'minimum_stock' => 5,
                'maximum_stock' => 30,
                'rack_number' => 'Rak 5',
                'shelf_level' => 'L4',
                'slot_number' => '05',
                'specific_location_code' => '1-R5-L4-05',
                'qr_code_payload' => 'HPK-PART|HYD-VLV-001',
                'stock_qty' => 15,
                'batch_lot_number' => 'LOT-2026-VLV',
            ],

            // RAK 1 (AREA 1 - RAW MATERIAL BAJA)
            [
                'part_number' => 'RAW-PLT-006',
                'name' => 'Pelat Baja Hardox 450 Tebal 6mm (Floor Plate)',
                'category' => 'raw_material',
                'uom' => 'Lembar',
                'specification' => 'Ukuran 1500 x 6000mm, Hardness 450 HBW, Abrasion Resistant untuk Lantai Dump',
                'minimum_stock' => 10,
                'maximum_stock' => 50,
                'rack_number' => 'Rak 1',
                'shelf_level' => 'L1',
                'slot_number' => '01',
                'specific_location_code' => '1-R1-L1-01',
                'qr_code_payload' => 'HPK-PART|RAW-PLT-006',
                'stock_qty' => 8,
                'batch_lot_number' => 'LOT-2026-RAW',
            ],

            // RAK 2 (AREA 1 - CANTILEVER PROFIL UNP)
            [
                'part_number' => 'RAW-UNP-150',
                'name' => 'Baja Profil Kanal UNP 150 x 75 x 6.5mm (6m)',
                'category' => 'raw_material',
                'uom' => 'Batang (6m)',
                'specification' => 'Standar JIS G3101 SS400, Panjang 6 Meter, Cross Member & Subframe Karoseri',
                'minimum_stock' => 15,
                'maximum_stock' => 80,
                'rack_number' => 'Rak 2',
                'shelf_level' => 'L1',
                'slot_number' => '02',
                'specific_location_code' => '1-R2-L1-02',
                'qr_code_payload' => 'HPK-PART|RAW-UNP-150',
                'stock_qty' => 42,
                'batch_lot_number' => 'LOT-2026-RAW',
            ],

            // RAK 4 (AREA 1 - BINS FASTENER & MUR)
            [
                'part_number' => 'FST-BLT-M16',
                'name' => 'Baut High Tensile Hex Grade 8.8 M16 x 50mm + Nut',
                'category' => 'fastener',
                'uom' => 'Pcs',
                'specification' => 'Grade 8.8 Black Finish, Pitch 2.0, Tensile Strength 800 MPa, DIN 933',
                'minimum_stock' => 200,
                'maximum_stock' => 1500,
                'rack_number' => 'Rak 4',
                'shelf_level' => 'L1',
                'slot_number' => '01',
                'specific_location_code' => '1-R4-L1-01',
                'qr_code_payload' => 'HPK-PART|FST-BLT-M16',
                'stock_qty' => 650,
                'batch_lot_number' => 'LOT-2026-FST',
            ],

            // RAK 6 (AREA 1 - CHEMICAL & CAT PU)
            [
                'part_number' => 'CHM-CAT-PUE',
                'name' => 'Topcoat Polyurethane HPK Dark Grey (20 Liter)',
                'category' => 'chemical_paint',
                'uom' => 'Liter',
                'specification' => 'Dua komponen (PU + Hardener), Ketahanan Korosi Tinggi & Tahan Sinar UV',
                'minimum_stock' => 40,
                'maximum_stock' => 200,
                'rack_number' => 'Rak 6',
                'shelf_level' => 'L1',
                'slot_number' => '01',
                'specific_location_code' => '1-R6-L1-01',
                'qr_code_payload' => 'HPK-PART|CHM-CAT-PUE',
                'stock_qty' => 80,
                'batch_lot_number' => 'LOT-2026-CHM',
            ],

            // PALLET 1 (AREA 1 - AREA NON-RAK: SILINDER HIDROLIK HEAVY STACKING)
            [
                'part_number' => 'HYD-CYL-200',
                'name' => 'Silinder Hidrolik Telescopic Heavy 200mm Karoseri',
                'category' => 'hydraulic',
                'uom' => 'Pcs',
                'specification' => 'Silinder hidrolik dump truck 30 ton, 5-stage, stroke 4200mm (Pallet Lantai)',
                'minimum_stock' => 2,
                'maximum_stock' => 15,
                'rack_number' => 'Pallet 1',
                'shelf_level' => 'L1',
                'slot_number' => '01',
                'specific_location_code' => '1-PLT1-01',
                'qr_code_payload' => 'HPK-PART|HYD-CYL-200',
                'stock_qty' => 6,
                'batch_lot_number' => 'LOT-2026-PLT',
            ],

            // PALLET 2 (AREA 1 - AREA NON-RAK: MOUNTING & ACCESSORIES PALLET STAGING)
            [
                'part_number' => 'ACC-MNT-DUT',
                'name' => 'Mounting Dudukan Kabin Dutro Heavy Karoseri',
                'category' => 'accessories',
                'uom' => 'Set',
                'specification' => 'Rubber mounting set + bracket baja sasis karoseri Hino Dutro 130HD',
                'minimum_stock' => 5,
                'maximum_stock' => 30,
                'rack_number' => 'Pallet 2',
                'shelf_level' => 'L1',
                'slot_number' => '01',
                'specific_location_code' => '1-PLT2-01',
                'qr_code_payload' => 'HPK-PART|ACC-MNT-DUT',
                'stock_qty' => 20,
                'batch_lot_number' => 'LOT-2026-PLT2',
            ],

            // PALLET 3 (AREA 1 - AREA NON-RAK: RAW MATERIAL PELAT STACKING)
            [
                'part_number' => 'RAW-PLT-HAR',
                'name' => 'Pelat Hardox 450 Tahan Aus 10mm x 1.5m x 6m',
                'category' => 'raw_material',
                'uom' => 'Batang (6m)',
                'specification' => 'Baja tahan abrasi tinggi untuk lantai bak dump truck quarry',
                'minimum_stock' => 3,
                'maximum_stock' => 20,
                'rack_number' => 'Pallet 3',
                'shelf_level' => 'L1',
                'slot_number' => '01',
                'specific_location_code' => '1-PLT3-01',
                'qr_code_payload' => 'HPK-PART|RAW-PLT-HAR',
                'stock_qty' => 12,
                'batch_lot_number' => 'LOT-2026-PLT3',
            ],
        ];

        foreach ($componentsData as $c) {
            $stockQty = $c['stock_qty'];
            $rackNumber = $c['rack_number'];
            $shelfLevel = $c['shelf_level'];
            $slotNumber = $c['slot_number'];
            $specificLocationCode = $c['specific_location_code'];
            $batchLotNumber = $c['batch_lot_number'];

            unset(
                $c['stock_qty'],
                $c['rack_number'],
                $c['shelf_level'],
                $c['slot_number'],
                $c['specific_location_code'],
                $c['batch_lot_number']
            );

            $location = $locations[$rackNumber] ?? null;
            $category = $categories[$c['category']] ?? null;
            $uom = $uoms[strtoupper($c['uom'])] ?? null;

            $component = Component::updateOrCreate(
                ['part_number' => $c['part_number']],
                array_merge($c, [
                    'default_location_id' => $location?->id,
                    'default_shelf_level' => $shelfLevel,
                    'default_slot_number' => $slotNumber,
                    'component_category_id' => $category?->id,
                    'uom_id' => $uom?->id,
                    'is_active' => true,
                ])
            );

            // Create or update specific stock balance with shelf level & slot
            if ($location) {
                StockBalance::updateOrCreate(
                    [
                        'component_id' => $component->id,
                        'location_id' => $location->id,
                        'shelf_level' => $shelfLevel,
                        'slot_number' => $slotNumber,
                    ],
                    [
                        'quantity' => $stockQty,
                        'specific_location_code' => $specificLocationCode,
                        'batch_lot_number' => $batchLotNumber,
                    ]
                );
            }
        }
    }
}
