<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterDataMigratorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Categories & Map existing components
        $categories = [
            'hydraulic' => ['Hydraulic Parts', 'Komponen Hidrolik'],
            'raw_material' => ['Raw Material', 'Bahan Baku'],
            'fastener' => ['Fastener & Hardware', 'Baut & Mur'],
            'accessories' => ['Accessories & Body', 'Aksesoris'],
            'electrical' => ['Electrical & Wiring', 'Kelistrikan'],
            'chemical_paint' => ['Chemical & Paint', 'Kimia & Cat'],
        ];

        foreach ($categories as $code => $info) {
            $cat = \App\Models\ComponentCategory::firstOrCreate(
                ['code' => $code],
                ['name' => $info[0], 'description' => $info[1]]
            );
            
            \App\Models\Component::where('category', $code)->update(['component_category_id' => $cat->id]);
        }

        // 2. Seed UOMs & Map existing components
        $uoms = \App\Models\Component::select('uom')->distinct()->pluck('uom');
        foreach ($uoms as $uomStr) {
            if (empty($uomStr)) continue;
            
            $uom = \App\Models\Uom::firstOrCreate(
                ['code' => strtoupper($uomStr)],
                ['name' => $uomStr]
            );
            
            \App\Models\Component::where('uom', $uomStr)->update(['uom_id' => $uom->id]);
        }

        // 3. Seed Warehouse & Zones & Map Locations
        $warehouse = \App\Models\Warehouse::firstOrCreate(
            ['code' => 'WH-HPK-1'],
            ['name' => 'HPK 1-Building Warehouse', 'description' => 'Gudang Utama Karoseri']
        );

        $existingZones = \App\Models\Location::select('zone_code', 'zone_name')->distinct()->get();
        foreach ($existingZones as $ez) {
            if (empty($ez->zone_code)) continue;
            
            $zone = \App\Models\Zone::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => $ez->zone_code],
                ['name' => $ez->zone_name ?? 'Zona ' . $ez->zone_code, 'type' => 'storage']
            );

            \App\Models\Location::where('zone_code', $ez->zone_code)->update([
                'warehouse_id' => $warehouse->id,
                'zone_id' => $zone->id
            ]);
        }
    }
}
