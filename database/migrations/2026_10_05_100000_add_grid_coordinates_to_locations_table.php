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
            $table->integer('grid_x')->nullable()->after('max_capacity');
            $table->integer('grid_y')->nullable()->after('grid_x');
            $table->integer('grid_w')->default(2)->after('grid_y');
            $table->integer('grid_h')->default(2)->after('grid_w');
            $table->string('color')->nullable()->after('grid_h');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['grid_x', 'grid_y', 'grid_w', 'grid_h', 'color']);
        });
    }
};
