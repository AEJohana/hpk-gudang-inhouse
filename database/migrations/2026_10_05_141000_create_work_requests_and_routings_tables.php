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
        // 1. Tabel Order Work Request Inhouse (Order Gudang ke Machine Center)
        Schema::create('work_requests', function (Blueprint $table) {
            $table->id();
            $table->string('wri_number', 50)->unique(); // WRI-202610-0001
            $table->foreignId('component_id')->constrained('components')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users');
            $table->foreignId('target_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('target_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('target_shelf_level', 10)->nullable(); // e.g. L3
            $table->string('target_slot_number', 10)->nullable(); // e.g. 05
            $table->decimal('quantity_requested', 10, 2);
            $table->decimal('quantity_produced', 10, 2)->default(0);
            $table->decimal('quantity_received', 10, 2)->default(0);
            $table->enum('priority', ['normal', 'high', 'urgent_line_stop'])->default('normal');
            $table->enum('status', ['submitted', 'in_production', 'ready_for_warehouse', 'received', 'cancelled'])->default('submitted');
            $table->date('due_date')->nullable();
            $table->string('spk_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Detail Tahapan Alur Mesin per WRI (Multi-Machine Routing)
        Schema::create('work_request_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_request_id')->constrained('work_requests')->cascadeOnDelete();
            $table->integer('step_number'); // 1 = Laser Cutting, 2 = Bending, 3 = ...
            $table->foreignId('machine_id')->constrained('machines');
            $table->string('process_name', 100); // e.g. Potong Plat Laser, Bending Tekuk
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending');
            $table->string('operator_name', 100)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_request_steps');
        Schema::dropIfExists('work_requests');
    }
};
