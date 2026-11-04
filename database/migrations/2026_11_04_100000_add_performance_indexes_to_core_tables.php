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
        // 1. Indeks untuk mempercepat pencarian dan filter Work Request Inhouse
        Schema::table('work_requests', function (Blueprint $table) {
            $table->index('status', 'idx_work_requests_status');
            $table->index('is_final', 'idx_work_requests_is_final');
            $table->index('approval_pengaju', 'idx_work_requests_app_pengaju');
            $table->index('approval_penerima', 'idx_work_requests_app_penerima');
            $table->index('created_at', 'idx_work_requests_created_at');
            $table->index(['dept_pengaju', 'dept_penerima'], 'idx_work_requests_depts');
            $table->index('spk_reference', 'idx_work_requests_spk');
        });

        // 2. Indeks untuk mempercepat pencarian transaksi harian dan filter tipe
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('transaction_date', 'idx_transactions_date');
            $table->index('type', 'idx_transactions_type');
            $table->index('created_at', 'idx_transactions_created_at');
        });

        // 3. Indeks untuk katalog master komponen
        Schema::table('components', function (Blueprint $table) {
            $table->index('name', 'idx_components_name');
            $table->index('category', 'idx_components_category');
            $table->index('is_active', 'idx_components_is_active');
        });

        // 4. Indeks komposit langkah mesin
        Schema::table('work_request_steps', function (Blueprint $table) {
            $table->index(['work_request_id', 'step_number'], 'idx_wr_steps_wr_step');
        });

        // 5. Indeks komposit log aktivitas WRI
        Schema::table('work_request_logs', function (Blueprint $table) {
            $table->index(['work_request_id', 'action_type'], 'idx_wr_logs_wr_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_requests', function (Blueprint $table) {
            $table->dropIndex('idx_work_requests_status');
            $table->dropIndex('idx_work_requests_is_final');
            $table->dropIndex('idx_work_requests_app_pengaju');
            $table->dropIndex('idx_work_requests_app_penerima');
            $table->dropIndex('idx_work_requests_created_at');
            $table->dropIndex('idx_work_requests_depts');
            $table->dropIndex('idx_work_requests_spk');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_date');
            $table->dropIndex('idx_transactions_type');
            $table->dropIndex('idx_transactions_created_at');
        });

        Schema::table('components', function (Blueprint $table) {
            $table->dropIndex('idx_components_name');
            $table->dropIndex('idx_components_category');
            $table->dropIndex('idx_components_is_active');
        });

        Schema::table('work_request_steps', function (Blueprint $table) {
            $table->dropIndex('idx_wr_steps_wr_step');
        });

        Schema::table('work_request_logs', function (Blueprint $table) {
            $table->dropIndex('idx_wr_logs_wr_action');
        });
    }
};
