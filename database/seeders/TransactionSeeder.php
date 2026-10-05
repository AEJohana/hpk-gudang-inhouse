<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\CycleCount;
use App\Models\CycleCountItem;
use App\Models\Disposal;
use App\Models\DisposalItem;
use App\Models\Ecr;
use App\Models\Location;
use App\Models\QrRequest;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@hydraxle.com')->first();
        $operator = User::where('email', 'operator@hydraxle.com')->first();
        $engineer = User::where('email', 'engineer@hydraxle.com')->first();

        $comps = Component::all()->keyBy('part_number');
        $locMap = [];
        foreach (Location::all() as $loc) {
            $locMap[$loc->rack_number] = $loc;
            if ($loc->rack_code) $locMap[$loc->rack_code] = $loc;
            if ($loc->rack_number === 'Rak 1' || $loc->rack_code === 'R1') $locMap['BAY-01'] = $loc;
            if ($loc->rack_number === 'Rak 2' || $loc->rack_code === 'R2') $locMap['CANT-01'] = $loc;
            if ($loc->rack_number === 'Rak 3' || $loc->rack_code === 'R3') $locMap['RK-ACC-01'] = $loc;
            if ($loc->rack_number === 'Rak 4' || $loc->rack_code === 'R4') $locMap['BIN-FST-01'] = $loc;
            if ($loc->rack_number === 'Rak 5' || $loc->rack_code === 'R5') {
                $locMap['RK-HYD-01'] = $loc;
                $locMap['RK-HYD-02'] = $loc;
                $locMap['RK-PTO-01'] = $loc;
            }
            if ($loc->rack_number === 'Rak 6' || $loc->rack_code === 'R6') $locMap['RK-CHM-01'] = $loc;
            if ($loc->rack_number === 'Rak 7' || $loc->rack_code === 'R7') $locMap['STG-01'] = $loc;
            if ($loc->rack_number === 'Rak 8' || $loc->rack_code === 'R8') $locMap['SCRAP-01'] = $loc;
        }
        $locations = $locMap;

        // 1. Inbound Transaction
        if ($admin && isset($comps['HYD-CYL-160']) && isset($locations['RK-HYD-01'])) {
            $txIn = Transaction::updateOrCreate(
                ['transaction_number' => 'TRX-IN-202610-001'],
                [
                    'type' => 'inbound',
                    'spk_number' => null,
                    'reference_document' => 'PO-HYD-9981 / SJ-88219',
                    'notes' => 'Penerimaan silinder hidrolik & pompa dari supplier import',
                    'user_id' => $admin->id,
                    'transaction_date' => now()->subDays(1),
                    'status' => 'completed',
                ]
            );

            TransactionItem::updateOrCreate(
                [
                    'transaction_id' => $txIn->id,
                    'component_id' => $comps['HYD-CYL-160']->id,
                ],
                [
                    'from_location_id' => null,
                    'to_location_id' => $locations['RK-HYD-01']->id,
                    'quantity' => 2,
                    'unit_price' => 18500000,
                    'notes' => 'Lolos uji inspeksi QC',
                ]
            );
        }

        // 2. Outbound Transaction
        if ($operator && isset($comps['RAW-PLT-006']) && isset($locations['BAY-01']) && isset($locations['STG-01'])) {
            $txOut = Transaction::updateOrCreate(
                ['transaction_number' => 'TRX-OUT-202610-001'],
                [
                    'type' => 'outbound',
                    'spk_number' => 'SPK-2026-DT-042',
                    'reference_document' => 'WO-FAB-102',
                    'notes' => 'Pengeluaran pelat Hardox & profil UNP untuk lini fabrikasi dump',
                    'user_id' => $operator->id,
                    'transaction_date' => now(),
                    'status' => 'completed',
                ]
            );

            TransactionItem::updateOrCreate(
                [
                    'transaction_id' => $txOut->id,
                    'component_id' => $comps['RAW-PLT-006']->id,
                ],
                [
                    'from_location_id' => $locations['BAY-01']->id,
                    'to_location_id' => $locations['STG-01']->id,
                    'quantity' => 2,
                    'unit_price' => 12500000,
                    'notes' => 'Diterima oleh Leader Fabrikasi Line 1',
                ]
            );
        }

        // 3. ECR (Engineering Change Request)
        if ($engineer && isset($comps['HYD-CYL-160'])) {
            Ecr::updateOrCreate(
                ['ecr_number' => 'ECR-2026-10-001'],
                [
                    'component_id' => $comps['HYD-CYL-160']->id,
                    'title' => 'Peningkatan Grade Seal Kit Silinder Telescopic Menjadi Viton High Temp',
                    'revision_type' => 'spec_change',
                    'old_specification' => 'Standard NBR Seal Kit max 80 Celcius',
                    'new_specification' => 'Viton High Temperature Seal Kit max 150 Celcius untuk operasi tambang batubara berat',
                    'reason' => 'Menghindari resiko rembesan oli hidrolik pada operasional dump tambang bersuhu tinggi dan debu ekstrem.',
                    'stock_policy' => 'run_out',
                    'status' => 'submitted',
                    'requested_by' => $engineer->id,
                ]
            );
        }

        // 4. Disposal Request
        if ($operator && isset($comps['RAW-PLT-006']) && isset($locations['SCRAP-01'])) {
            $disposal = Disposal::updateOrCreate(
                ['disposal_number' => 'DSP-2026-10-001'],
                [
                    'disposal_type' => 'scrap_iron',
                    'reason' => 'Sisa potongan (offcut) pelat Hardox & profil baja hasil pemotongan plasma cutting yang tidak dapat dipakai lagi.',
                    'estimated_weight_kg' => 480.00,
                    'estimated_salvage_value' => 2880000,
                    'status' => 'submitted',
                    'requested_by' => $operator->id,
                ]
            );

            DisposalItem::updateOrCreate(
                [
                    'disposal_id' => $disposal->id,
                    'component_id' => $comps['RAW-PLT-006']->id,
                ],
                [
                    'from_location_id' => $locations['SCRAP-01']->id,
                    'quantity' => 12,
                    'condition_description' => 'Potongan pelat tidak beraturan sisa potong bevel',
                ]
            );
        }

        // 5. QR Label Request
        if ($admin && isset($comps['HYD-CYL-160'])) {
            QrRequest::updateOrCreate(
                ['request_number' => 'QR-REQ-202610-001'],
                [
                    'component_id' => $comps['HYD-CYL-160']->id,
                    'label_type' => 'item',
                    'print_qty' => 5,
                    'notes' => 'Label stiker thermal untuk unit silinder baru tiba',
                    'status' => 'pending',
                    'requested_by' => $admin->id,
                ]
            );
        }

        // 6. Cycle Count (Stock Opname)
        if ($operator && isset($comps['HYD-CYL-160']) && isset($comps['HYD-PMP-082']) && isset($locations['RK-HYD-01']) && isset($locations['RK-HYD-02'])) {
            $count = CycleCount::updateOrCreate(
                ['count_number' => 'CC-202610-001'],
                [
                    'zone_target' => 'B',
                    'count_date' => now(),
                    'notes' => 'Stok Opname Rutin Mingguan Zona B (Komponen Hidrolik)',
                    'status' => 'pending_review',
                    'conducted_by' => $operator->id,
                ]
            );

            CycleCountItem::updateOrCreate(
                [
                    'cycle_count_id' => $count->id,
                    'component_id' => $comps['HYD-CYL-160']->id,
                ],
                [
                    'location_id' => $locations['RK-HYD-01']->id,
                    'system_qty' => 3,
                    'physical_qty' => 3,
                    'variance_qty' => 0,
                    'variance_reason' => 'Sesuai fisik',
                ]
            );

            CycleCountItem::updateOrCreate(
                [
                    'cycle_count_id' => $count->id,
                    'component_id' => $comps['HYD-PMP-082']->id,
                ],
                [
                    'location_id' => $locations['RK-HYD-02']->id,
                    'system_qty' => 12,
                    'physical_qty' => 11,
                    'variance_qty' => -1,
                    'variance_reason' => '1 unit sedang diuji di test bench hidrolik belum dicatat bon sementara',
                ]
            );
        }
    }
}
