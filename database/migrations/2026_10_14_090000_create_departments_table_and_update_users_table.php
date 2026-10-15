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
        // 1. Master Tabel Departemen Resmi (Acuan 15 Departemen Legacy Karoseri HPK)
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // 1, 2, ..., 15, SPO, etc.
            $table->string('name', 150); // Dept Laser Cutting, Dept M1, etc.
            $table->string('alias', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Modifikasi Tabel Users: Hubungkan department_id, can_access_all_departments (*), dan signature_path
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('role')->constrained('departments')->nullOnDelete();
            $table->boolean('can_access_all_departments')->default(false)->after('department_id');
            $table->string('signature_path')->nullable()->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['department_id', 'can_access_all_departments', 'signature_path']);
        });

        Schema::dropIfExists('departments');
    }
};
