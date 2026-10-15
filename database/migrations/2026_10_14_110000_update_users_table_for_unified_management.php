<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure all users have a username before setting NOT NULL
        $usersWithoutUsername = DB::table('users')->whereNull('username')->orWhere('username', '')->get();
        foreach ($usersWithoutUsername as $u) {
            $baseUsername = Str::slug($u->name, '_') ?: 'user_' . $u->id;
            $candidate = $baseUsername;
            $counter = 1;
            while (DB::table('users')->where('username', $candidate)->where('id', '!=', $u->id)->exists()) {
                $candidate = $baseUsername . '_' . $counter++;
            }
            DB::table('users')->where('id', $u->id)->update(['username' => $candidate]);
        }

        Schema::table('users', function (Blueprint $table) {
            // Make email nullable
            $table->string('email')->nullable()->change();
            // Make username not null
            $table->string('username', 100)->nullable(false)->change();
            
            // Add work_area if not exists
            if (!Schema::hasColumn('users', 'work_area')) {
                $table->string('work_area', 50)->default('gudang')->after('role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'work_area')) {
                $table->dropColumn('work_area');
            }
            $table->string('email')->nullable(false)->change();
            $table->string('username', 100)->nullable()->change();
        });
    }
};
