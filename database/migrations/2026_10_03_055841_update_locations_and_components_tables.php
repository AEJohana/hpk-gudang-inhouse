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
        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('id')->constrained('warehouses')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->after('warehouse_id')->constrained('zones')->nullOnDelete();
        });

        Schema::table('components', function (Blueprint $table) {
            $table->foreignId('component_category_id')->nullable()->after('category')->constrained('component_categories')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->after('uom')->constrained('uoms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['zone_id']);
            $table->dropColumn(['warehouse_id', 'zone_id']);
        });

        Schema::table('components', function (Blueprint $table) {
            $table->dropForeign(['component_category_id']);
            $table->dropForeign(['uom_id']);
            $table->dropColumn(['component_category_id', 'uom_id']);
        });
    }
};
