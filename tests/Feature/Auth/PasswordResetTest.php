<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('phone')->nullable();
            $table->string('role')->default('operator');
            $table->string('work_area')->nullable();
            $table->string('department')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->boolean('can_access_all_departments')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });
    }

    public function test_forgot_password_helpdesk_screen_can_be_rendered(): void
    {
        $this->withoutExceptionHandling();
        $adminRole = Role::create(['name' => 'admin_gudang']);
        $admin = User::create([
            'name' => 'Budi Santoso',
            'username' => 'admin_gudang',
            'phone' => '0812-3456-7890',
            'role' => 'admin_gudang',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $admin->syncRoles([$adminRole]);

        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('BANTUAN PEMULIHAN AKUN');
        $response->assertSee('Lupa Kata Sandi?');
        $response->assertSee('Budi Santoso');
        $response->assertSee('Admin Gudang');
        $response->assertSee('0812-3456-7890');
    }

    public function test_forgot_password_post_redirects_with_security_notice(): void
    {
        $response = $this->post('/forgot-password', ['username' => 'operator_joni']);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('info');
    }

    public function test_admin_can_quick_reset_user_password_to_default(): void
    {
        $adminRole = Role::create(['name' => 'admin_gudang']);
        $operatorRole = Role::create(['name' => 'operator']);

        $admin = User::create([
            'name' => 'Budi Santoso',
            'username' => 'admin_test_reset',
            'role' => 'admin_gudang',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $admin->syncRoles([$adminRole]);

        $operator = User::create([
            'name' => 'Joni Kuswanto',
            'username' => 'operator_lupa_pwd',
            'role' => 'operator',
            'password' => Hash::make('old_secret_pwd'),
            'is_active' => true,
        ]);
        $operator->syncRoles([$operatorRole]);

        $response = $this->actingAs($admin)->post(route('admin.users.quick-reset-password', $operator));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        // Pastikan password di DB sudah menjadi 'hpkgudang'
        $operator->refresh();
        $this->assertTrue(Hash::check('hpkgudang', $operator->password));

        // Logout admin terlebih dahulu, lalu login sebagai operator
        $this->post('/logout');

        // Verifikasi bahwa operator sekarang bisa login menggunakan 'hpkgudang'
        $loginResponse = $this->post('/login', [
            'username' => $operator->username,
            'password' => 'hpkgudang',
        ]);
        $this->assertAuthenticatedAs($operator);
    }

    public function test_non_admin_cannot_quick_reset_password(): void
    {
        $operatorRole = Role::create(['name' => 'operator']);

        $operator1 = User::create([
            'name' => 'Joni',
            'username' => 'operator_1',
            'role' => 'operator',
            'password' => Hash::make('pwd1'),
            'is_active' => true,
        ]);
        $operator1->syncRoles([$operatorRole]);

        $operator2 = User::create([
            'name' => 'Denis',
            'username' => 'operator_2',
            'role' => 'operator',
            'password' => Hash::make('pwd2'),
            'is_active' => true,
        ]);
        $operator2->syncRoles([$operatorRole]);

        $response = $this->actingAs($operator1)->post(route('admin.users.quick-reset-password', $operator2));

        // Harus ditolak (403 Forbidden karena middleware role:admin_gudang|supervisor)
        $response->assertStatus(403);
    }
}
