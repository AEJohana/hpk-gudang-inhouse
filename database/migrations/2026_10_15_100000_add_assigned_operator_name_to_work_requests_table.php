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
            $table->string('assigned_operator_name', 100)->nullable()->after('approval_penerima');
            $table->decimal('current_batch_qty', 10, 2)->default(0)->after('quantity_defect');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_requests', function (Blueprint $table) {
            $table->dropColumn(['assigned_operator_name', 'current_batch_qty']);
        });
    }
};
