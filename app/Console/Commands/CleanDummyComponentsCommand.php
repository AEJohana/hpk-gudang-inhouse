<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Models\CycleCountItem;
use App\Models\DisposalItem;
use App\Models\Ecr;
use App\Models\QrRequest;
use App\Models\StockBalance;
use App\Models\TransactionItem;
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
            // 1. Bersihkan transaksi child seeder yang merujuk ke dummy
            $sbDeleted = StockBalance::whereIn('component_id', $ids)->delete();
            $txDeleted = TransactionItem::whereIn('component_id', $ids)->delete();
            $ecrDeleted = Ecr::whereIn('component_id', $ids)->delete();
            $qrDeleted = QrRequest::whereIn('component_id', $ids)->delete();
            $ccDeleted = CycleCountItem::whereIn('component_id', $ids)->delete();
            $dispDeleted = DisposalItem::whereIn('component_id', $ids)->delete();

            // 2. Hapus komponen dummy
            $compDeleted = Component::whereIn('id', $ids)->delete();

            DB::commit();

            $this->info("==================================================================");
            $this->info(" PEMBERSIHAN DUMMY BERHASIL 100%");
            $this->info("==================================================================");
            $this->line("Komponen Dihapus      : <info>{$compDeleted}</info> part");
            $this->line("Saldo Stok Dihapus    : <comment>{$sbDeleted}</comment> baris");
            $this->line("Item Transaksi Dihapus: <comment>{$txDeleted}</comment> baris");
            $this->line("Item ECR/QR/CC Dihapus: <comment>" . ($ecrDeleted + $qrDeleted + $ccDeleted + $dispDeleted) . "</comment> baris");
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
