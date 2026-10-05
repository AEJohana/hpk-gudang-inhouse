<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_balances', function (Blueprint $table) {
            $table->string('shelf_level', 20)->nullable()->after('quantity'); // e.g. L1, L2, L3, L4
            $table->string('slot_number', 20)->nullable()->after('shelf_level'); // e.g. 01, 02, 05
            $table->string('specific_location_code', 50)->nullable()->after('slot_number'); // e.g. 1-R3-L3-05
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->string('rack_code', 20)->nullable()->after('rack_number'); // e.g. R1, R2, R3, R5
            $table->integer('total_levels')->default(4)->after('bin_level'); // Default 4 floors (L1-L4)
            $table->integer('slots_per_level')->default(6)->after('total_levels'); // Default 6 slots (01-06)
        });

        Schema::table('components', function (Blueprint $table) {
            $table->string('default_shelf_level', 20)->nullable()->after('default_location_id'); // e.g. L3
            $table->string('default_slot_number', 20)->nullable()->after('default_shelf_level'); // e.g. 05
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_balances', function (Blueprint $table) {
            $table->dropColumn(['shelf_level', 'slot_number', 'specific_location_code']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['rack_code', 'total_levels', 'slots_per_level']);
        });

        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn(['default_shelf_level', 'default_slot_number']);
        });
    }
};
