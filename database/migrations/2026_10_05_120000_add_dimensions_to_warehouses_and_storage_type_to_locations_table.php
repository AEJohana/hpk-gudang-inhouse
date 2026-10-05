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
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'grid_columns')) {
                $table->integer('grid_columns')->default(16)->after('description');
            }
            if (!Schema::hasColumn('warehouses', 'grid_rows')) {
                $table->integer('grid_rows')->default(12)->after('grid_columns');
            }
            if (!Schema::hasColumn('warehouses', 'width_meters')) {
                $table->decimal('width_meters', 8, 2)->default(24.00)->after('grid_rows');
            }
            if (!Schema::hasColumn('warehouses', 'length_meters')) {
                $table->decimal('length_meters', 8, 2)->default(32.00)->after('width_meters');
            }
        });

        Schema::table('locations', function (Blueprint $table) {
            if (!Schema::hasColumn('locations', 'storage_type')) {
                $table->string('storage_type', 30)->default('rack')->after('rack_number'); // 'rack' or 'pallet'
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['grid_columns', 'grid_rows', 'width_meters', 'length_meters']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['storage_type']);
        });
    }
};
