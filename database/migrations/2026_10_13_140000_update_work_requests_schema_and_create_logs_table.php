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
        Schema::table('work_requests', function (Blueprint $table) {
            $table->string('status', 100)->default('Dalam antrian')->change();
            $table->string('dept_pengaju', 100)->nullable()->after('requested_by_user_id');
            $table->string('dept_penerima', 100)->nullable()->after('dept_pengaju');
            $table->string('routing_text', 255)->nullable()->after('dept_penerima');
            $table->string('approval_pengaju', 50)->default('Diterima')->after('routing_text');
            $table->string('approval_penerima', 50)->default('Diterima')->after('approval_pengaju');
            $table->string('current_process_status', 100)->default('Dalam antrian')->after('status');
            $table->decimal('quantity_defect', 10, 2)->default(0)->after('quantity_received');
            $table->boolean('is_final')->default(false)->after('quantity_defect');
        });

        Schema::create('work_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_request_id')->constrained('work_requests')->cascadeOnDelete();
            $table->string('action_type', 50); // process, partial, defect, pickup, drop, final_pickup, final_receive
            $table->string('status_label', 150); // e.g. "Proses: Dept Laser Cutting (In)", "Parsial > Siap diambil"
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('quantity_accumulated', 10, 2)->default(0);
            $table->string('operator_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_request_logs');

        Schema::table('work_requests', function (Blueprint $table) {
            $table->dropColumn([
                'dept_pengaju',
                'dept_penerima',
                'routing_text',
                'approval_pengaju',
                'approval_penerima',
                'current_process_status',
                'quantity_defect',
                'is_final',
            ]);
            $table->enum('status', ['submitted', 'in_production', 'ready_for_warehouse', 'received', 'cancelled'])->default('submitted')->change();
        });
    }
};
