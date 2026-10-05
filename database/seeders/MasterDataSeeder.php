<?php

namespace Database\Seeders;

use App\Models\ComponentCategory;
use App\Models\Location;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Warehouses (Gedung 1 & Gedung 2 dengan Luas Area dan Grid Berbeda)
        $warehouse = Warehouse::updateOrCreate(
            ['code' => 'WH-HPK-1'],
            [
                'name' => 'Gedung 1 - Pabrik Karoseri HPK (Dump & Chassis)',
                'description' => 'Gudang Utama Karoseri Dump Truck, Chassis Hino & Komponen Hidrolik Presisi',
                'grid_columns' => 16,
                'grid_rows' => 12,
                'width_meters' => 24.00,
                'length_meters' => 32.00,
                'is_active' => true,
            ]
        );

        $warehouse2 = Warehouse::updateOrCreate(
            ['code' => 'WH-HPK-2'],
            [
                'name' => 'Gedung 2 - Fabrikasi Bodi & Assembly Trailer',
                'description' => 'Gudang Fabrikasi Rangka Baja, Perakitan Tangki & Buffer Staging Trailer',
                'grid_columns' => 20,
                'grid_rows' => 14,
                'width_meters' => 28.00,
                'length_meters' => 40.00,
                'is_active' => true,
            ]
        );

        // 2. Warehouse Zones / Areas
        $zonesData = [
            [
                'code' => '1',
                'name' => 'Area 1 - Pabrik Karoseri HPK',
                'type' => 'storage',
                'description' => 'Area Utama Penyimpanan Komponen Karoseri, Chassis Hino, Hidrolik & Baja',
            ],
            [
                'code' => 'A',
                'name' => 'Raw Material Baja',
                'type' => 'storage',
                'description' => 'Area Stacking Pelat Baja Tebal (Under Crane) & Rak Profil UNP/WF',
            ],
            [
                'code' => 'B',
                'name' => 'Komponen Hidrolik & Presisi',
                'type' => 'storage',
                'description' => 'Pallet Rack Silinder Hidrolik Telescopic, Heavy Duty Pompa & PTO',
            ],
            [
                'code' => 'C',
                'name' => 'Hardware & Fastener',
                'type' => 'storage',
                'description' => 'Multi-tier Bins Baut High Tensile, Mur, Aksesoris & Lampu LED',
            ],
            [
                'code' => 'D',
                'name' => 'Chemical & Cat',
                'type' => 'storage',
                'description' => 'Ruang Berventilasi Khusus Drum Cat PU, Primer, Thinner & Sealant',
            ],
            [
                'code' => 'E',
                'name' => 'Staging Perakitan Karoseri',
                'type' => 'staging',
                'description' => 'Buffer Material Siap Masuk Assembly Karoseri',
            ],
            [
                'code' => 'F',
                'name' => 'Karantina & Scrap Yard',
                'type' => 'quarantine',
                'description' => 'Penampungan Afkir & Potongan Pelat Besi Tua Sisa Fabrikasi',
            ],
        ];

        $zones = [];
        foreach ($zonesData as $zData) {
            $zones[$zData['code']] = Zone::updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'code' => $zData['code'],
                ],
                [
                    'name' => $zData['name'],
                    'type' => $zData['type'],
                    'description' => $zData['description'],
                    'is_active' => true,
                ]
            );
        }

        // 3. Component Categories
        $categoriesData = [
            [
                'code' => 'hydraulic',
                'name' => 'Komponen Hidrolik & Presisi',
                'description' => 'Silinder hidrolik telescopic, pompa gear, power take-off (PTO), dan valve control.',
            ],
            [
                'code' => 'raw_material',
                'name' => 'Raw Material Baja',
                'description' => 'Pelat baja Hardox/Mild Steel, profil UNP, WF, dan pipa seamless struktur.',
            ],
            [
                'code' => 'fastener',
                'name' => 'Hardware & Fastener',
                'description' => 'Baut high tensile Grade 8.8/10.9, mur flens, ring plat, dan rivet karoseri.',
            ],
            [
                'code' => 'accessories',
                'name' => 'Aksesoris & Mounting Karoseri',
                'description' => 'Mounting cabin Hino, engsel pintu dump, twist lock kontainer, handle bodi.',
            ],
            [
                'code' => 'electrical',
                'name' => 'Electrical & Lighting',
                'description' => 'Lampu belakang LED karoseri 24V, marker lamp, wiring harness, dan relay box.',
            ],
            [
                'code' => 'chemical_paint',
                'name' => 'Chemical & Cat',
                'description' => 'Topcoat polyurethane (PU), primer epoxy anti karat, thinner PU, dan sealant.',
            ],
        ];

        foreach ($categoriesData as $cData) {
            ComponentCategory::updateOrCreate(
                ['code' => $cData['code']],
                [
                    'name' => $cData['name'],
                    'description' => $cData['description'],
                    'is_active' => true,
                ]
            );
        }

        // 4. Units of Measure (UOM)
        $uomsData = [
            ['code' => 'PCS', 'name' => 'Pcs'],
            ['code' => 'SET', 'name' => 'Set'],
            ['code' => 'BATANG (6M)', 'name' => 'Batang (6m)'],
            ['code' => 'LEMBAR', 'name' => 'Lembar'],
            ['code' => 'KG', 'name' => 'Kg'],
            ['code' => 'LITER', 'name' => 'Liter'],
            ['code' => 'BOX', 'name' => 'Box'],
            ['code' => 'METER', 'name' => 'Meter'],
        ];

        foreach ($uomsData as $uData) {
            Uom::updateOrCreate(
                ['code' => $uData['code']],
                [
                    'name' => $uData['name'],
                    'is_active' => true,
                ]
            );
        }

        // 5. Warehouse Physical Racks (Displayed cleanly as "Rak 3", "Rak 5", etc.)
        $locationsData = [
            // Area 1 - Rak 3: Mounting Hino, Chassis & Aksesoris Karoseri
            [
                'zone_code' => '1',
                'zone_name' => 'Area 1',
                'aisle' => 'Lorong 2',
                'rack_number' => 'Rak 3',
                'rack_code' => 'R3',
                'bin_level' => 'Lantai 1-4',
                'total_levels' => 4,
                'slots_per_level' => 6,
                'description' => 'Rak Komponen Mounting Hino, Bracket Chassis & Aksesoris Karoseri',
                'max_capacity' => 60,
                'grid_x' => 4,
                'grid_y' => 5,
                'grid_w' => 1,
                'grid_h' => 1,
                'color' => 'blue',
            ],

            // Area 1 - Rak 5: Komponen Hidrolik Presisi & PTO
            [
                'zone_code' => '1',
                'zone_name' => 'Area 1',
                'aisle' => 'Lorong 3',
                'rack_number' => 'Rak 5',
                'rack_code' => 'R5',
                'bin_level' => 'Lantai 1-4',
                'total_levels' => 4,
                'slots_per_level' => 6,
                'description' => 'Pallet Rack Silinder Telescopic, Heavy Duty Pompa & PTO Hino',
                'max_capacity' => 60,
                'grid_x' => 10,
                'grid_y' => 5,
                'grid_w' => 1,
                'grid_h' => 1,
                'color' => 'emerald',
            ],

            // Area 1 - Rak 1: Raw Material Pelat Baja
            [
                'zone_code' => '1',
                'zone_name' => 'Area 1',
                'aisle' => 'Lorong 1',
                'rack_number' => 'Rak 1',
                'rack_code' => 'R1',
                'bin_level' => 'Floor',
                'total_levels' => 2,
                'slots_per_level' => 4,
                'description' => 'Area Stacking Pelat Baja Tebal (Under Overhead Crane)',
                'max_capacity' => 80,
                'grid_x' => 2,
                'grid_y' => 2,
                'grid_w' => 1,
                'grid_h' => 1,
                'color' => 'indigo',
            ],

            // Area 1 - Rak 2: Rak Cantilever UNP & WF 6 Meter
            [
                'zone_code' => '1',
                'zone_name' => 'Area 1',
                'aisle' => 'Lorong 1',
                'rack_number' => 'Rak 2',
                'rack_code' => 'R2',
                'bin_level' => 'Level 1-3',
                'total_levels' => 3,
                'slots_per_level' => 4,
                'description' => 'Rak Cantilever Profil Baja UNP & WF Struktur 6 Meter',
                'max_capacity' => 120,
                'grid_x' => 6,
                'grid_y' => 2,
                'grid_w' => 1,
                'grid_h' => 1,
                'color' => 'indigo',
            ],

            // Area 1 - Rak 4: Multi-tier Bins Baut & Fastener
            [
                'zone_code' => '1',
                'zone_name' => 'Area 1',
                'aisle' => 'Lorong 2',
                'rack_number' => 'Rak 4',
                'rack_code' => 'R4',
                'bin_level' => 'Bin 01-06',
                'total_levels' => 4,
                'slots_per_level' => 6,
                'description' => 'Multi-tier Bins Baut High Tensile Grade 8.8, Mur & Ring',
                'max_capacity' => 200,
                'grid_x' => 7,
                'grid_y' => 5,
                'grid_w' => 1,
                'grid_h' => 1,
                'color' => 'amber',
            ],

            // Area 1 - Rak 6: Ruang Berventilasi Khusus Chemical & Cat PU
            [
                'zone_code' => '1',
                'zone_name' => 'Area 1',
                'aisle' => 'Lorong 3',
                'rack_number' => 'Rak 6',
                'rack_code' => 'R6',
                'bin_level' => 'Floor',
                'total_levels' => 2,
                'slots_per_level' => 4,
                'description' => 'Ruang B3 Berventilasi Khusus Drum Cat Polyurethane & Thinner',
                'max_capacity' => 50,
                'grid_x' => 14,
                'grid_y' => 2,
                'grid_w' => 1,
                'grid_h' => 1,
                'color' => 'purple',
            ],

            // Area 1 - Rak 7: Buffer Staging Lini Perakitan
            [
                'zone_code' => '1',
                'zone_name' => 'Area 1',
                'aisle' => 'Buffer 01',
                'rack_number' => 'Rak 7',
                'rack_code' => 'R7',
                'bin_level' => 'Floor',
                'total_levels' => 1,
                'slots_per_level' => 4,
                'description' => 'Buffer Material Transit Siap Masuk Assembly Perakitan Karoseri',
                'max_capacity' => 60,
                'grid_x' => 4,
                'grid_y' => 11,
                'grid_w' => 1,
                'grid_h' => 1,
                'color' => 'cyan',
            ],

            // Area 1 - Rak 8: Scrap Yard & Karantina Afkir
            [
                'zone_code' => '1',
                'zone_name' => 'Area 1',
                'aisle' => 'Area Belakang',
                'rack_number' => 'Rak 8',
                'rack_code' => 'R8',
                'storage_type' => 'rack',
                'bin_level' => 'Yard',
                'total_levels' => 1,
                'slots_per_level' => 4,
                'description' => 'Penampungan Afkir & Potongan Pelat Besi Tua Sebelum Disposal',
                'max_capacity' => 150,
                'grid_x' => 14,
                'grid_y' => 10,
                'grid_w' => 1,
                'grid_h' => 1,
                'color' => 'rose',
            ],

            // Area 1 - Pallet 1: Pallet Lantai Silinder Hidrolik Telescopic (Area Non-Rak)
            [
                'zone_code' => '1',
                'zone_name' => 'Area 1',
                'aisle' => 'Pallet Stacking A',
                'rack_number' => 'Pallet 1',
                'rack_code' => 'PLT1',
                'storage_type' => 'pallet',
                'bin_level' => 'Floor Pallet',
                'total_levels' => 1,
                'slots_per_level' => 2,
                'description' => 'Area Lantai Pallet: Stacking Silinder Hidrolik Telescopic 5-Stage & Pompa Dump',
                'max_capacity' => 20,
                'grid_x' => 2,
                'grid_y' => 8,
                'grid_w' => 1,
                'grid_h' => 1,
                'color' => 'amber',
            ],

            // Area 1 - Pallet 2: Pallet Lantai Mounting Cabin & Aksesoris Dump (Area Non-Rak)
            [
                'zone_code' => '1',
                'zone_name' => 'Area 1',
                'aisle' => 'Pallet Stacking B',
                'rack_number' => 'Pallet 2',
                'rack_code' => 'PLT2',
                'storage_type' => 'pallet',
                'bin_level' => 'Floor Pallet',
                'total_levels' => 1,
                'slots_per_level' => 2,
                'description' => 'Area Lantai Pallet: Staging Bracket Mounting Cabin Hino & Spareparts Karoseri',
                'max_capacity' => 30,
                'grid_x' => 5,
                'grid_y' => 8,
                'grid_w' => 1,
                'grid_h' => 1,
                'color' => 'amber',
            ],

            // Area 1 - Pallet 3: Pallet Lantai Raw Material Pelat Potong (Area Non-Rak)
            [
                'zone_code' => '1',
                'zone_name' => 'Area 1',
                'aisle' => 'Pallet Stacking C',
                'rack_number' => 'Pallet 3',
                'rack_code' => 'PLT3',
                'storage_type' => 'pallet',
                'bin_level' => 'Floor Pallet',
                'total_levels' => 1,
                'slots_per_level' => 2,
                'description' => 'Area Lantai Pallet: Karantina Potongan Pelat Hardox & Fitting Fabrikasi',
                'max_capacity' => 40,
                'grid_x' => 8,
                'grid_y' => 8,
                'grid_w' => 1,
                'grid_h' => 1,
                'color' => 'amber',
            ],
        ];

        foreach ($locationsData as $loc) {
            $zoneId = isset($zones[$loc['zone_code']]) ? $zones[$loc['zone_code']]->id : null;

            Location::updateOrCreate(
                ['rack_number' => $loc['rack_number']],
                [
                    'warehouse_id' => $warehouse->id,
                    'zone_id' => $zoneId,
                    'zone_code' => $loc['zone_code'],
                    'zone_name' => $loc['zone_name'],
                    'aisle' => $loc['aisle'],
                    'bin_level' => $loc['bin_level'],
                    'storage_type' => $loc['storage_type'] ?? 'rack',
                    'rack_code' => $loc['rack_code'],
                    'total_levels' => $loc['total_levels'],
                    'slots_per_level' => $loc['slots_per_level'],
                    'description' => $loc['description'],
                    'max_capacity' => $loc['max_capacity'],
                    'grid_x' => $loc['grid_x'],
                    'grid_y' => $loc['grid_y'],
                    'grid_w' => $loc['grid_w'],
                    'grid_h' => $loc['grid_h'],
                    'color' => $loc['color'],
                ]
            );
        }

        // Gedung 2 Zone & Locations
        $zoneGedung2 = Zone::updateOrCreate(
            ['warehouse_id' => $warehouse2->id, 'code' => '2'],
            [
                'name' => 'Area Fabrikasi & Staging Trailer',
                'type' => 'storage',
                'description' => 'Area Fabrikasi Rangka Baja, Perakitan Tangki & Buffer Staging Trailer',
                'is_active' => true,
            ]
        );

        $locationsGedung2 = [
            [
                'warehouse_id' => $warehouse2->id,
                'zone_id' => $zoneGedung2->id,
                'zone_code' => '2',
                'zone_name' => 'Area Fabrikasi & Staging Trailer',
                'aisle' => 'Aisle Fabrikasi 1',
                'rack_number' => 'Rak 1 (Gedung 2)',
                'rack_code' => 'R1',
                'storage_type' => 'rack',
                'bin_level' => 'Lantai 1-3',
                'total_levels' => 3,
                'slots_per_level' => 4,
                'description' => 'Rak Profil Baja UNP Rangka Trailer & Crossmember',
                'max_capacity' => 80,
                'grid_x' => 3,
                'grid_y' => 4,
                'grid_w' => 1,
                'grid_h' => 1,
                'color' => 'blue',
            ],
            [
                'warehouse_id' => $warehouse2->id,
                'zone_id' => $zoneGedung2->id,
                'zone_code' => '2',
                'zone_name' => 'Area Fabrikasi & Staging Trailer',
                'aisle' => 'Staging Trailer',
                'rack_number' => 'Pallet 1 (Gedung 2)',
                'rack_code' => 'PLT1',
                'storage_type' => 'pallet',
                'bin_level' => 'Floor Pallet',
                'total_levels' => 1,
                'slots_per_level' => 2,
                'description' => 'Pallet Staging Landing Gear & Axle Trailer 3-Axle',
                'max_capacity' => 15,
                'grid_x' => 8,
                'grid_y' => 6,
                'grid_w' => 1,
                'grid_h' => 1,
                'color' => 'amber',
            ],
        ];

        foreach ($locationsGedung2 as $loc2) {
            Location::updateOrCreate(
                ['rack_number' => $loc2['rack_number']],
                $loc2
            );
        }
    }
}
