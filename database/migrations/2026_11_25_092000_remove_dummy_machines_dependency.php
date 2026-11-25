<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Melepas ketergantungan wajib ke tabel dummy machines secara aman tanpa menghapus data riwayat WRI.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql' && Schema::hasTable('work_request_steps')) {
            // 1. Lepas Foreign Key ke machines jika masih ada
            $fks = DB::select("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'work_request_steps'
                  AND COLUMN_NAME = 'machine_id'
                  AND REFERENCED_TABLE_NAME = 'machines'
            ");

            foreach ($fks as $fk) {
                DB::statement("ALTER TABLE work_request_steps DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            // 2. Ubah machine_id menjadi boleh kosong (NULL) dan kosongkan nilainya tanpa menghapus data tiket
            DB::statement("ALTER TABLE work_request_steps MODIFY machine_id BIGINT UNSIGNED NULL");
            DB::table('work_request_steps')->update(['machine_id' => null]);

            // 3. Bersihkan 7 baris data mesin dummy dari tabel machines
            if (Schema::hasTable('machines')) {
                DB::table('machines')->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak memulihkan data dummy machines demi menjaga kebersihan arsitektur berbasis departemen.
    }
};
