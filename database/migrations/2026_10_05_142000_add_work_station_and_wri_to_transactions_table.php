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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('work_station_id')->nullable()->after('notes')->constrained('work_stations')->nullOnDelete();
            $table->foreignId('work_request_id')->nullable()->after('work_station_id')->constrained('work_requests')->nullOnDelete();
            $table->string('recipient_name', 100)->nullable()->after('work_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['work_station_id']);
            $table->dropForeign(['work_request_id']);
            $table->dropColumn(['work_station_id', 'work_request_id', 'recipient_name']);
        });
    }
};
