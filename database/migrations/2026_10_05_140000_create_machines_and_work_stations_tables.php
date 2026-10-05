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
        // 1. Master Mesin Utama / Machine Center
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // MC-LC-01, MC-BND-01, MC-CNC-01, etc.
            $table->string('name', 100); // Mesin Fiber Laser Cutting 6kW, Mesin Press Brake Bending 250T
            $table->string('machine_type', 50); // laser_cutting, bending, machining, lathe, welding, shearing
            $table->string('location_workshop', 100)->default('Workshop Fabrikasi Utama');
            $table->string('status', 30)->default('ready'); // ready, in_operation, maintenance
            $table->decimal('hourly_capacity', 8, 2)->nullable();
            $table->string('operator_default', 100)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Master Stasiun Kerja / Lini Perakitan (Tujuan Distribusi Supply Gudang)
        Schema::create('work_stations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // WS-DUMP-01, WS-TANK-01, WS-MIXR-01, WS-SUB-MNT
            $table->string('name', 100); // Line Perakitan Dump Truck, Line Tangki Karoseri
            $table->string('area_name', 100)->default('Workshop Assembling Karoseri');
            $table->string('pic_name', 100)->nullable(); // Supervisor / Mandor Lini
            $table->string('status', 30)->default('active'); // active, inactive
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_stations');
        Schema::dropIfExists('machines');
    }
};
