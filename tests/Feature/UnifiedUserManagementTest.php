<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnifiedUserManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Department $laserDept;
    protected Department $gudangDept;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed departments, roles & permissions
        $this->artisan('db:seed', ['--class' => 'DepartmentSeeder']);
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        $this->laserDept = Department::where('code', '1')->orWhere('name', 'like', '%Laser%')->first();
        $this->gudangDept = Department::where('code', '11')->orWhere('name', 'like', '%Gudang%')->first();

        $this->admin = User::firstOrCreate(
            ['username' => 'admin_test'],
            [
                'name' => 'Admin Test',
                'email' => 'admin.test@hydraxle.com',
                'password' => bcrypt('password'),
                'work_area' => 'semua',
                'department_id' => $this->gudangDept->id,
                'can_access_all_departments' => true,
                'is_active' => true,
            ]
        );

        $this->admin->syncRoles(['admin_gudang']);
    }

    public function test_user_index_screen_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('Manajemen Pengguna', false);
        $response->assertSee('Filter Bagian', false);
        $response->assertSee('Departemen Pabrik', false);
        $response->assertSee($this->admin->name);
        $response->assertSee('@' . $this->admin->username);
    }

    public function test_user_create_screen_renders_with_hierarchy_and_presets(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users/create');

        $response->assertStatus(200);
        $response->assertSee('Tambah Pengguna', false);
        $response->assertSee('1. Identitas Akun & Kredensial Masuk', false);
        $response->assertSee('2. Pemilihan Role & Bagian Operasional', false);
        $response->assertSee('Departemen Pabrik', false);
        $response->assertSee('4. Peran & Otorisasi Sistem', false);
        $response->assertSee('Work Request Inhouse (WRI)', false);
    }

    public function test_admin_can_create_user_with_role_work_area_and_custom_permissions(): void
    {
        $uniqueUser = 'op_mesin_' . uniqid();
        $userData = [
            'name' => 'Operator Mesin Test',
            'username' => $uniqueUser,
            'phone' => '08123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'operator',
            'work_area' => 'mesin_center',
            'department_id' => $this->laserDept->id,
            'can_access_all_departments' => 0,
            'is_active' => 1,
            'permissions' => [
                'work-request.view',
                'work-request.process',
                'work-request.print',
                'master-komponen.view',
            ],
        ];

        $response = $this->actingAs($this->admin)->post('/admin/users', $userData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success');

        $createdUser = User::where('username', $uniqueUser)->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('Operator Mesin Test', $createdUser->name);
        $this->assertNull($createdUser->email);
        $this->assertEquals('mesin_center', $createdUser->work_area);
        $this->assertEquals('Mesin Center', $createdUser->work_area_label);
        $this->assertEquals($this->laserDept->id, $createdUser->department_id);
        $this->assertTrue($createdUser->hasRole('operator'));
        $this->assertTrue($createdUser->can('work-request.view'));
        $this->assertTrue($createdUser->can('work-request.process'));
        $this->assertTrue($createdUser->can('work-request.print'));
        $this->assertFalse($createdUser->can('work-request.approve'));
    }

    public function test_admin_can_create_supervisor_with_automatic_global_scope(): void
    {
        $uniqueUser = 'spv_global_' . uniqid();
        $userData = [
            'name' => 'Supervisor Fabrikasi Global',
            'username' => $uniqueUser,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'supervisor',
            'is_active' => 1,
            'permissions' => [
                'work-request.view',
                'work-request.approve',
            ],
        ];

        $response = $this->actingAs($this->admin)->post('/admin/users', $userData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/admin/users');

        $createdUser = User::where('username', $uniqueUser)->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('semua', $createdUser->work_area);
        $this->assertEquals('Seluruh Area', $createdUser->work_area_label);
        $this->assertTrue($createdUser->can_access_all_departments);
        $this->assertTrue($createdUser->hasRole('supervisor'));
    }

    public function test_admin_can_create_operator_gudang_with_auto_locked_department(): void
    {
        $uniqueUser = 'op_gudang_' . uniqid();
        $userData = [
            'name' => 'Operator Gudang Part',
            'username' => $uniqueUser,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'operator',
            'work_area' => 'gudang',
            // department_id omitted on purpose, must auto-lock to Dept Gudang
            'is_active' => 1,
            'permissions' => [
                'peta-gudang.view',
                'transaksi-komponen.create',
            ],
        ];

        $response = $this->actingAs($this->admin)->post('/admin/users', $userData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/admin/users');

        $createdUser = User::where('username', $uniqueUser)->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('gudang', $createdUser->work_area);
        $this->assertEquals('Gudang', $createdUser->work_area_label);
        $this->assertEquals($this->gudangDept->id, $createdUser->department_id);
        $this->assertTrue($createdUser->hasRole('operator'));
    }

    public function test_user_can_login_using_username_or_email(): void
    {
        $uname = 'dual_user_' . uniqid();
        $uemail = 'dual_' . uniqid() . '@example.com';

        $testUser = User::create([
            'name' => 'Dual Login User',
            'username' => $uname,
            'email' => $uemail,
            'password' => bcrypt('secretpassword'),
            'work_area' => 'material_handling',
            'department_id' => $this->gudangDept->id,
            'is_active' => true,
        ]);
        $testUser->syncRoles(['staff']);

        // 1. Test login with username
        $loginWithUsername = $this->post('/login', [
            'email' => $uname,
            'password' => 'secretpassword',
        ]);
        $loginWithUsername->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($testUser);

        // Logout
        $this->post('/logout');
        $this->assertGuest();

        // 2. Test login with email
        $loginWithEmail = $this->post('/login', [
            'email' => $uemail,
            'password' => 'secretpassword',
        ]);
        $loginWithEmail->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($testUser);
    }

    public function test_admin_can_update_user_role_work_area_and_permissions(): void
    {
        $uname = 'staff_' . uniqid();
        $uemail = 'init_' . uniqid() . '@example.com';
        $promotedEmail = 'promoted_' . uniqid() . '@example.com';

        $targetUser = User::create([
            'name' => 'Initial Staff',
            'username' => $uname,
            'email' => $uemail,
            'password' => bcrypt('password123'),
            'work_area' => 'gudang',
            'department_id' => $this->gudangDept->id,
            'is_active' => true,
        ]);
        $targetUser->syncRoles(['staff']);

        $updateData = [
            'name' => 'Promoted Supervisor',
            'username' => $uname,
            'email' => $promotedEmail,
            'role' => 'supervisor',
            'work_area' => 'mesin_center',
            'department_id' => $this->laserDept->id,
            'can_access_all_departments' => 1,
            'is_active' => 1,
            'permissions' => [
                'work-request.view',
                'work-request.approve',
                'work-request.safety-stock',
            ],
        ];

        $response = $this->actingAs($this->admin)->put("/admin/users/{$targetUser->id}", $updateData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/admin/users');
        $targetUser->refresh();

        $this->assertEquals('Promoted Supervisor', $targetUser->name);
        $this->assertEquals($promotedEmail, $targetUser->email);
        $this->assertEquals('semua', $targetUser->work_area);
        $this->assertEquals('Seluruh Area', $targetUser->work_area_label);
        $this->assertTrue($targetUser->can_access_all_departments);
        $this->assertTrue($targetUser->hasRole('supervisor'));
        $this->assertTrue($targetUser->can('work-request.approve'));
    }

    public function test_filter_users_by_work_area_and_role(): void
    {
        $gudangName = 'Gudang Person ' . uniqid();
        $mhName = 'MH Person ' . uniqid();

        User::create([
            'name' => $gudangName,
            'username' => 'gudang_' . uniqid(),
            'password' => bcrypt('password'),
            'work_area' => 'gudang',
            'department_id' => $this->gudangDept->id,
            'is_active' => true,
        ])->syncRoles(['staff']);

        User::create([
            'name' => $mhName,
            'username' => 'mh_' . uniqid(),
            'password' => bcrypt('password'),
            'work_area' => 'material_handling',
            'department_id' => $this->gudangDept->id,
            'is_active' => true,
        ])->syncRoles(['operator']);

        // Filter work_area = material_handling
        $response = $this->actingAs($this->admin)->get('/admin/users?work_area=material_handling');
        $response->assertStatus(200);
        $response->assertSee($mhName);
        $response->assertDontSee($gudangName);
    }
}
