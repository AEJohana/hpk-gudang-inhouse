<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = DB::table('settings')->where('key', 'wr_scan_required')->exists();
        if (!$exists) {
            DB::table('settings')->insert([
                'key' => 'wr_scan_required',
                'value' => '0', // Default NONAKTIF (OFF)
                'type' => 'boolean',
                'group' => 'operational',
                'description' => 'Wajibkan Scan QR untuk Proses Pemesinan, MH & Penerimaan Gudang',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->where('key', 'wr_scan_required')->delete();
    }
};
