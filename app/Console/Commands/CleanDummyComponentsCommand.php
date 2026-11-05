<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Models\CycleCount;
use App\Models\CycleCountItem;
use App\Models\Disposal;
use App\Models\DisposalItem;
use App\Models\Ecr;
use App\Models\QrRequest;
use App\Models\StockBalance;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\WorkRequest;
use App\Models\WorkRequestStep;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanDummyComponentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'components:clean-dummies {--force : Jalankan pembersihan tanpa prompt interaktif}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan 15 part dummy seeder awal dan part tes manual dari database operasional secara aman';

    /**
     * Daftar Kode Part Dummy & Tes yang disahkan untuk dibersihkan.
     * Komponen operasional asli HPK (seperti CSBA10320130000 dan CRDA10200390000L) TIDAK AKAN DIHAPUS.
     */
    public const TARGET_DUMMY_PART_NUMBERS = [
        'CSFP10300250082', // Bracket Mounting Depan Hino (Seeder Rak 3)
        'ACC-UBT-M20',     // Baut U-Bolt Chassis M20 x 350mm + Double Nut
        'ACC-TRN-HIN',     // Trunnion Bushing Set Hino FM Rear Suspension
        'HYD-CYL-160',     // Silinder Hidrolik Telescopic 5-Stage 160mm
        'HYD-PMP-082',     // Hydraulic Gear Pump 82L Bi-Rotational
        'HYD-PTO-HIN',     // Power Take-Off (PTO) Transmission Hino FM260
        'HYD-VLV-001',     // Pneumatic Tipping Control Valve Karoseri
        'RAW-PLT-006',     // Pelat Baja Hardox 450 Tebal 6mm (Floor Plate)
        'RAW-UNP-150',     // Baja Profil Kanal UNP 150 x 75 x 6.5mm (6m)
        'FST-BLT-M16',     // Baut High Tensile Hex Grade 8.8 M16 x 50mm + Nut
        'CHM-CAT-PUE',     // Topcoat Polyurethane HPK Dark Grey (20 Liter)
        'HYD-CYL-200',     // Silinder Hidrolik Telescopic Heavy 200mm Karoseri
        'ACC-MNT-DUT',     // Mounting Dudukan Kabin Dutro Heavy Karoseri
        'RAW-PLT-HAR',     // Pelat Hardox 450 Tahan Aus 10mm x 1.5m x 6m
        'NEW-20261026-LAGL'// Part Tes Manual Pengguna "cek cek"
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("==================================================================");
        $this->info(" HPK GUDANG - PEMBERSIHAN AMAN DATA DUMMY SEEDER & PART TES");
        $this->info("==================================================================");

        $components = Component::whereIn('part_number', self::TARGET_DUMMY_PART_NUMBERS)->get();
        $count = $components->count();

        if ($count === 0) {
            $this->info("Tidak ditemukan data dummy seeder atau part tes di database (Sudah bersih).");
            return 0;
        }

        $this->warn("Ditemukan {$count} komponen dummy/tes yang siap dibersihkan:");
        $tableRows = [];
        foreach ($components as $c) {
            $tableRows[] = [
                'ID' => $c->id,
                'Part Number' => $c->part_number,
                'Nama Komponen' => $c->name,
                'Kategori' => $c->category ?: '-',
                'Stok' => $c->total_stock,
            ];
        }
        $this->table(['ID', 'Part Number', 'Nama Komponen', 'Kategori', 'Stok'], $tableRows);

        $this->line("<comment>Catatan Penting:</comment> Komponen asli operasional karoseri HPK (termasuk part tanpa foto seperti CSBA10320130000 dan CRDA10200390000L) TIDAK AKAN DISENTUH.");

        if (!$this->option('force') && !$this->confirm("Apakah Anda yakin ingin menghapus {$count} komponen dummy di atas beserta relasi seeder-nya?")) {
            $this->info("Operasi dibatalkan.");
            return 0;
        }

        $ids = $components->pluck('id')->toArray();

        DB::beginTransaction();
        try {
            // 1. Bersihkan sample Work Request seeder (beserta step pengerjaannya)
            $dummyWriIds = WorkRequest::whereIn('wri_number', ['WRI-202610-0001', 'WRI-202610-0002'])
                ->orWhereIn('component_id', $ids)
                ->pluck('id')
                ->toArray();
            $wrStepsDeleted = WorkRequestStep::whereIn('work_request_id', $dummyWriIds)->delete();
            $wriDeleted = WorkRequest::whereIn('id', $dummyWriIds)->delete();

            // 2. Bersihkan transaksi seeder (item dan header)
            $dummyTxIds = Transaction::whereIn('transaction_number', ['TRX-IN-202610-001', 'TRX-OUT-202610-001'])
                ->pluck('id')
                ->toArray();
            $txItemsDeleted = TransactionItem::whereIn('transaction_id', $dummyTxIds)
                ->orWhereIn('component_id', $ids)
                ->delete();
            $txDeleted = Transaction::whereIn('id', $dummyTxIds)->delete();

            // 3. Bersihkan ECR seeder
            $ecrDeleted = Ecr::whereIn('ecr_number', ['ECR-2026-10-001'])
                ->orWhereIn('component_id', $ids)
                ->delete();

            // 4. Bersihkan Disposal seeder (item dan header)
            $dummyDspIds = Disposal::whereIn('disposal_number', ['DSP-2026-10-001'])->pluck('id')->toArray();
            $dispItemsDeleted = DisposalItem::whereIn('disposal_id', $dummyDspIds)
                ->orWhereIn('component_id', $ids)
                ->delete();
            $dispDeleted = Disposal::whereIn('id', $dummyDspIds)->delete();

            // 5. Bersihkan QR Request seeder
            $qrDeleted = QrRequest::whereIn('request_number', ['QR-REQ-202610-001'])
                ->orWhereIn('component_id', $ids)
                ->delete();

            // 6. Bersihkan Cycle Count seeder (item dan header)
            $dummyCcIds = CycleCount::whereIn('count_number', ['CC-202610-001'])->pluck('id')->toArray();
            $ccItemsDeleted = CycleCountItem::whereIn('cycle_count_id', $dummyCcIds)
                ->orWhereIn('component_id', $ids)
                ->delete();
            $ccDeleted = CycleCount::whereIn('id', $dummyCcIds)->delete();

            // 7. Bersihkan saldo stok dummy seeder (termasuk saldo duplikat batch LOT-2026-HIN)
            $sbDeleted = StockBalance::whereIn('component_id', $ids)
                ->orWhere('batch_lot_number', 'LOT-2026-HIN')
                ->delete();

            // 8. Hapus master komponen dummy
            $compDeleted = Component::whereIn('id', $ids)->delete();

            DB::commit();

            $this->info("==================================================================");
            $this->info(" PEMBERSIHAN DUMMY SEEDER BERHASIL 100%");
            $this->info("==================================================================");
            $this->line("Komponen Dihapus      : <info>{$compDeleted}</info> part");
            $this->line("Saldo Stok Dihapus    : <comment>{$sbDeleted}</comment> baris");
            $this->line("Sample WRI Dihapus    : <comment>{$wriDeleted}</comment> tiket ({$wrStepsDeleted} step)");
            $this->line("Transaksi Dihapus     : <comment>{$txDeleted}</comment> transaksi ({$txItemsDeleted} item)");
            $this->line("Item ECR/QR/CC/Disposal: <comment>" . ($ecrDeleted + $qrDeleted + $ccDeleted + $dispDeleted) . "</comment> dokumen");
            $this->info("Database sekarang murni berisi data master komponen resmi karoseri HPK!");

            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Gagal membersihkan data dummy: " . $e->getMessage());
            Log::error("Gagal clean dummies: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return 1;
        }
    }
}
