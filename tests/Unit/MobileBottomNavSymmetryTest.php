<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileBottomNavSymmetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('department')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

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
    }

    /**
     * Test single-access user (e.g. Operator Mesin with WR only) gets balanced 5-slot bottom nav.
     */
    public function test_single_wr_access_user_has_symmetrical_five_slots(): void
    {
        Permission::create(['name' => 'work-request.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'work-request.create', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Operator Laser',
            'email' => 'laser@hpk.test',
            'password' => bcrypt('secret'),
            'department' => 'Laser',
        ]);
        $user->givePermissionTo(['work-request.view', 'work-request.create']);
        $this->actingAs($user);

        $view = $this->view('layouts.app', ['slot' => '<div>Content</div>']);

        $html = (string) $view;

        // Assert 5 slots are rendered symmetrically around SCAN
        $this->assertStringContainsString('+ Buat WR', $html);
        $this->assertStringContainsString('mobile-scanner-fab', $html);

        // Extract mobile bottom nav content
        preg_match('/<div class="mobile-bottom-nav[^>]*>(.*?)<\/div>/s', $html, $matches);
        $this->assertNotEmpty($matches);
        $navContent = $matches[1];

        $this->assertStringContainsString('Home', $navContent);
        $this->assertStringContainsString('WRI', $navContent);
        $this->assertStringContainsString('SCAN', $navContent);
        $this->assertStringContainsString('+ Buat WR', $navContent);
        $this->assertStringContainsString('Menu', $navContent);

        // Assert Transaksi and MH navigation items are NOT in mobile nav for WR-only user
        $this->assertStringNotContainsString('<span>Transaksi</span>', $navContent);
        $this->assertStringNotContainsString('<span>MH</span>', $navContent);
    }

    /**
     * Test warehouse user with Master Komponen and Transaksi gets 5-slot layout.
     */
    public function test_warehouse_user_has_symmetrical_five_slots(): void
    {
        Permission::create(['name' => 'master-komponen.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'transaksi-komponen.view', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Admin Gudang',
            'email' => 'gudang@hpk.test',
            'password' => bcrypt('secret'),
            'department' => 'Gudang',
        ]);
        $user->givePermissionTo(['master-komponen.view', 'transaksi-komponen.view']);
        $this->actingAs($user);

        $view = $this->view('layouts.app', ['slot' => '<div>Content</div>']);

        $view->assertSee('Home');
        $view->assertSee('Komponen');
        $view->assertSee('SCAN');
        $view->assertSee('Transaksi');
        $view->assertSee('Menu');
    }

    /**
     * Test logistics user with WRI and Material Handling gets 5-slot layout.
     */
    public function test_logistics_user_has_symmetrical_five_slots(): void
    {
        Permission::create(['name' => 'work-request.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'material-handling.view', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Kurir MH',
            'email' => 'mh@hpk.test',
            'password' => bcrypt('secret'),
            'department' => 'Gudang',
        ]);
        $user->givePermissionTo(['work-request.view', 'material-handling.view']);
        $this->actingAs($user);

        $view = $this->view('layouts.app', ['slot' => '<div>Content</div>']);

        $view->assertSee('Home');
        $view->assertSee('WRI');
        $view->assertSee('SCAN');
        $view->assertSee('MH');
        $view->assertSee('Menu');
    }
}
