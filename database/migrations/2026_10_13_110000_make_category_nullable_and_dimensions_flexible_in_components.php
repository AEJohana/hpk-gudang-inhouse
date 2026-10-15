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
            $table->string('category')->nullable()->change();
            $table->string('length_mm', 100)->nullable()->change();
            $table->string('width_or_diameter_mm', 100)->nullable()->change();
            $table->string('thickness_or_height_mm', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->string('category')->nullable(false)->change();
            $table->decimal('length_mm', 10, 2)->nullable()->change();
            $table->decimal('width_or_diameter_mm', 10, 2)->nullable()->change();
            $table->decimal('thickness_or_height_mm', 10, 2)->nullable()->change();
        });
    }
};
