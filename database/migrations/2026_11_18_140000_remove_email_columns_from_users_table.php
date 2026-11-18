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
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropUnique('users_email_unique');
            } catch (\Throwable $e) {
                // Ignore if index does not exist
            }

            $columnsToDrop = [];
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $columnsToDrop[] = 'email_verified_at';
            }
            if (Schema::hasColumn('users', 'email')) {
                $columnsToDrop[] = 'email';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('phone');
            }
        });
    }
};
