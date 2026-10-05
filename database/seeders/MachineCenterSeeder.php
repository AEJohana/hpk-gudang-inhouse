<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\Location;
use App\Models\Machine;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkRequest;
use App\Models\WorkRequestStep;
use App\Models\WorkStation;
use Illuminate\Database\Seeder;

class MachineCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Mesin Utama / Machine Center
        $machines = [
            [
                'code' => 'MC-LC-01',
                'name' => 'Mesin Fiber Laser Cutting 6kW',
                'machine_type' => 'laser_cutting',
                'location_workshop' => 'Workshop Fabrikasi Utama (Bay 1)',
                'status' => 'ready',
                'hourly_capacity' => 12.5,
                'operator_default' => 'Joko Sutrisno (Operator Laser)',
                'description' => 'Pemotongan presisi tinggi pelat baja hitam/bordes s.d. ketebalan 25mm untuk komponen karoseri.',
            ],
            [
                'code' => 'MC-BND-01',
                'name' => 'Mesin Press Brake Bending 250T CNC',
                'machine_type' => 'bending',
                'location_workshop' => 'Workshop Fabrikasi Utama (Bay 2)',
                'status' => 'ready',
                'hourly_capacity' => 20.0,
                'operator_default' => 'Bambang Irawan (Operator Bending CNC)',
                'description' => 'Penekukan presisi sudut multi-angle untuk bracket mounting sasis dan dinding karoseri dump truck.',
            ],
            [
                'code' => 'MC-BND-02',
                'name' => 'Mesin Press Brake Bending 120T',
                'machine_type' => 'bending',
                'location_workshop' => 'Workshop Fabrikasi Presisi (Bay 3)',
                'status' => 'ready',
                'hourly_capacity' => 25.0,
                'operator_default' => 'Slamet Riyadi',
                'description' => 'Penekukan pelat tipis dan aksesoris engsel pintu karoseri serta penguat tangki.',
            ],
            [
                'code' => 'MC-SHR-01',
                'name' => 'Mesin Shearing Cutting Plate 16mm',
                'machine_type' => 'shearing',
                'location_workshop' => 'Workshop Raw Material Plate (Bay 1)',
                'status' => 'ready',
                'hourly_capacity' => 30.0,
                'operator_default' => 'Edi Susanto',
                'description' => 'Pemotongan lurus pelat dasar karoseri sebelum proses potong detail.',
            ],
            [
                'code' => 'MC-LATHE-01',
                'name' => 'Mesin Bubut Heavy Duty 3 Meter',
                'machine_type' => 'lathe',
                'location_workshop' => 'Workshop Bubut & Permesinan (Bay 4)',
                'status' => 'ready',
                'hourly_capacity' => 6.0,
                'operator_default' => 'Anton Wijaya',
                'description' => 'Pembuatan pin engsel dump body, bushing silinder hidrolik, dan as PTO karoseri.',
            ],
            [
                'code' => 'MC-CNC-01',
                'name' => 'Mesin CNC Milling & Drilling Center',
                'machine_type' => 'machining',
                'location_workshop' => 'Workshop Bubut & Permesinan (Bay 4)',
                'status' => 'ready',
                'hourly_capacity' => 8.0,
                'operator_default' => 'Dwi Handoko',
                'description' => 'Pengeboran lubang baut mounting sasis, flange sambungan hidrolik, dan manifold valve block.',
            ],
            [
                'code' => 'MC-WLD-01',
                'name' => 'Stasiun Sub-Assembly Welding Karoseri',
                'machine_type' => 'welding',
                'location_workshop' => 'Workshop Fabrikasi Assembling (Bay 5)',
                'status' => 'ready',
                'hourly_capacity' => 15.0,
                'operator_default' => 'Rudi Hartono (Welder 3G/4G)',
                'description' => 'Pengelasan penyatuan pelat potong laser dan bracket tekuk sebelum serah terima ke gudang.',
            ],
        ];

        foreach ($machines as $mData) {
            Machine::updateOrCreate(['code' => $mData['code']], $mData);
        }

        // 2. Seed Stasiun Kerja / Lini Perakitan Karoseri HPK
        $workStations = [
            [
                'code' => 'WS-DUMP-01',
                'name' => 'Line Perakitan Dump Truck Heavy Duty',
                'area_name' => 'Workshop Assembling Karoseri Gedung Barat',
                'pic_name' => 'Supriyadi (Mandor Dump Truck)',
                'status' => 'active',
                'description' => 'Lini utama assembling dump body karoseri truk Hino, Isuzu Giga, Fuso Fighter & FAW.',
            ],
            [
                'code' => 'WS-TANK-01',
                'name' => 'Line Fabrikasi & Assembling Tangki Karoseri',
                'area_name' => 'Workshop Assembling Karoseri Gedung Timur',
                'pic_name' => 'Haryanto (Mandor Line Tangki)',
                'status' => 'active',
                'description' => 'Perakitan tangki cairan CPO, semen curah, air & BBM dengan sekat baffle dan bracket chassis.',
            ],
            [
                'code' => 'WS-MIXR-01',
                'name' => 'Line Perakitan Truk Mixer Molen',
                'area_name' => 'Workshop Assembling Karoseri Gedung Utara',
                'pic_name' => 'Wahyudi (Mandor Mixer)',
                'status' => 'active',
                'description' => 'Perakitan tabung molen mixer beton, roller subframe, gearbox penggerak dan corong tuang.',
            ],
            [
                'code' => 'WS-SUB-MNT',
                'name' => 'Stasiun Sub-Assembly Mounting & Bracket Karoseri',
                'area_name' => 'Workshop Pre-Assembly Line',
                'pic_name' => 'Gunawan (Mandor Sub-Assembly)',
                'status' => 'active',
                'description' => 'Penyatuan awal bantalan karet mounting, baut pengikat sasis, dan bracket pelat karoseri.',
            ],
            [
                'code' => 'WS-HYD-01',
                'name' => 'Line Instalasi Hydraulic, Pompa & PTO Transmisi',
                'area_name' => 'Workshop Mekanikal & Hidrolik Karoseri',
                'pic_name' => 'Kurniawan (Kepala Teknisi Hidrolik)',
                'status' => 'active',
                'description' => 'Instalasi silinder dump hoist, pipa hidrolik tekanan tinggi, valve pengatur dan pompa gear PTO.',
            ],
            [
                'code' => 'WS-FIN-01',
                'name' => 'Stasiun Finishing, Sandblasting & Pengecatan Karoseri',
                'area_name' => 'Spray Booth & Oven Gedung Selatan',
                'pic_name' => 'Mulyono (Supervisor Painting)',
                'status' => 'active',
                'description' => 'Pembersihan sandblast, primer epoxy tahan karat, dan pengecatan Polyurethane akhir bodi truk.',
            ],
        ];

        foreach ($workStations as $wsData) {
            WorkStation::updateOrCreate(['code' => $wsData['code']], $wsData);
        }

        // 3. Seed Sample Work Request Inhouse (WRI)
        $adminUser = User::first();
        $warehouse = Warehouse::first();
        $rak3 = Location::where('rack_number', 'Rak 3')->first();
        $mountingComp = Component::where('part_number', 'CSFP10300250081')->first();
        $bracketComp = Component::where('part_number', 'CSFP10300250082')->first();
        $laserMachine = Machine::where('code', 'MC-LC-01')->first();
        $bendingMachine = Machine::where('code', 'MC-BND-01')->first();

        if ($mountingComp && $adminUser && $laserMachine && $bendingMachine) {
            // WRI 1: Sedang Berjalan di Mesin (Laser Cutting Selesai, Bending In-Progress)
            $wri1 = WorkRequest::updateOrCreate(
                ['wri_number' => 'WRI-202610-0001'],
                [
                    'component_id' => $mountingComp->id,
                    'requested_by_user_id' => $adminUser->id,
                    'target_warehouse_id' => $warehouse?->id,
                    'target_location_id' => $rak3?->id,
                    'target_shelf_level' => 'L3',
                    'target_slot_number' => '05',
                    'quantity_requested' => 20,
                    'quantity_produced' => 20,
                    'quantity_received' => 0,
                    'priority' => 'high',
                    'status' => 'in_production',
                    'due_date' => now()->addDays(2),
                    'spk_reference' => 'SPK-DT-2026-HINO-500',
                    'notes' => 'Komponen Mounting Hino 2 untuk pemenuhan batch perakitan 10 unit dump truck. Plat baja SS400 8mm.',
                ]
            );

            // Step 1: Laser Cutting (Completed)
            WorkRequestStep::updateOrCreate(
                ['work_request_id' => $wri1->id, 'step_number' => 1],
                [
                    'machine_id' => $laserMachine->id,
                    'process_name' => 'Potong Plat Baja Sasis (Laser Cutting)',
                    'status' => 'completed',
                    'operator_name' => 'Joko Sutrisno',
                    'started_at' => now()->subHours(4),
                    'completed_at' => now()->subHours(2),
                    'notes' => 'Toleransi potong presisi laser ±0.2mm. Hasil potong bersih dan rapi.',
                ]
            );

            // Step 2: Bending (In Progress)
            WorkRequestStep::updateOrCreate(
                ['work_request_id' => $wri1->id, 'step_number' => 2],
                [
                    'machine_id' => $bendingMachine->id,
                    'process_name' => 'Penekukan Sudut & Flange Mounting (Bending 250T)',
                    'status' => 'in_progress',
                    'operator_name' => 'Bambang Irawan',
                    'started_at' => now()->subHours(1),
                    'completed_at' => null,
                    'notes' => 'Sedang proses bending 90 derajat flange pengunci subframe.',
                ]
            );
        }

        if ($bracketComp && $adminUser && $laserMachine && $bendingMachine) {
            // WRI 2: Siap Kirim ke Gudang (Ready for Warehouse / Putaway)
            $wri2 = WorkRequest::updateOrCreate(
                ['wri_number' => 'WRI-202610-0002'],
                [
                    'component_id' => $bracketComp->id,
                    'requested_by_user_id' => $adminUser->id,
                    'target_warehouse_id' => $warehouse?->id,
                    'target_location_id' => $rak3?->id,
                    'target_shelf_level' => 'L3',
                    'target_slot_number' => '02',
                    'quantity_requested' => 15,
                    'quantity_produced' => 15,
                    'quantity_received' => 0,
                    'priority' => 'normal',
                    'status' => 'ready_for_warehouse',
                    'due_date' => now()->addDay(),
                    'spk_reference' => 'SPK-DT-2026-FRONT-MNT',
                    'notes' => 'Bracket Dudukan Depan Karoseri Hino. Telah lolos inspeksi QC mesin dan siap diterima gudang.',
                ]
            );

            // Step 1: Laser Cutting (Completed)
            WorkRequestStep::updateOrCreate(
                ['work_request_id' => $wri2->id, 'step_number' => 1],
                [
                    'machine_id' => $laserMachine->id,
                    'process_name' => 'Potong Plat Baja Depan (Laser Cutting)',
                    'status' => 'completed',
                    'operator_name' => 'Joko Sutrisno',
                    'started_at' => now()->subHours(6),
                    'completed_at' => now()->subHours(4),
                    'notes' => 'Potong 15 pcs plat 6mm selesai.',
                ]
            );

            // Step 2: Bending (Completed)
            WorkRequestStep::updateOrCreate(
                ['work_request_id' => $wri2->id, 'step_number' => 2],
                [
                    'machine_id' => $bendingMachine->id,
                    'process_name' => 'Tekuk Flange Depan (Bending 250T)',
                    'status' => 'completed',
                    'operator_name' => 'Bambang Irawan',
                    'started_at' => now()->subHours(3),
                    'completed_at' => now()->subHour(),
                    'notes' => 'Tekukan 15 pcs tuntas. Siap dikirim ke Gudang.',
                ]
            );
        }
    }
}
