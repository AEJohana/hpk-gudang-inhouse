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
        Schema::table('components', function (Blueprint $table) {
            // 1. Add structured dimensions in millimeters
            $table->decimal('length_mm', 10, 2)->nullable()->after('uom');
            $table->decimal('width_or_diameter_mm', 10, 2)->nullable()->after('length_mm');
            $table->decimal('thickness_or_height_mm', 10, 2)->nullable()->after('width_or_diameter_mm');

            // 2. Adjust stock constraint flexibility
            $table->integer('minimum_stock')->nullable()->default(0)->change();
            $table->integer('maximum_stock')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn(['length_mm', 'width_or_diameter_mm', 'thickness_or_height_mm']);
            $table->integer('minimum_stock')->default(5)->change();
            $table->integer('maximum_stock')->default(100)->change();
        });
    }
};
