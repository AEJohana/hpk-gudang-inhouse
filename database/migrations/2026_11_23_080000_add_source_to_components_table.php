<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('components', 'source')) {
            Schema::table('components', function (Blueprint $table) {
                $table->string('source', 20)->default('master')->index()->after('is_active');
            });
        }

        // Backfill data aman: tandai seluruh part ad-hoc WR lama sebagai 'wr_adhoc'
        DB::table('components')
            ->where(function ($query) {
                $query->where('part_number', 'like', 'NEW-%')
                      ->orWhere('specification', 'like', '%form Work Request%')
                      ->orWhere('part_number', '=', 'Baru');
            })
            ->update(['source' => 'wr_adhoc']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('components', 'source')) {
            Schema::table('components', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
    }
};
