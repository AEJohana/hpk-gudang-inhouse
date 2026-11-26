<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Department;
use App\Models\Location;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WorkRequestCreatePerformanceTest extends TestCase
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
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('work_area')->nullable();
            $table->string('department')->nullable();
            $table->boolean('can_access_all_departments')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->string('zone_code')->nullable();
            $table->string('zone_name')->nullable();
            $table->string('aisle')->nullable();
            $table->string('rack_number')->nullable();
            $table->string('storage_type')->nullable();
            $table->string('rack_code')->nullable();
            $table->string('bin_level')->nullable();
            $table->integer('total_levels')->default(1);
            $table->integer('slots_per_level')->default(1);
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('max_capacity')->default(100);
            $table->integer('grid_x')->default(0);
            $table->integer('grid_y')->default(0);
            $table->integer('grid_w')->default(1);
            $table->integer('grid_h')->default(1);
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('part_number')->nullable();
            $table->string('uom')->default('PCS');
            $table->string('category')->nullable();
            $table->string('length_mm')->nullable();
            $table->string('width_or_diameter_mm')->nullable();
            $table->string('thickness_or_height_mm')->nullable();
            $table->string('specification')->nullable();
            $table->decimal('minimum_stock', 12, 2)->default(0);
            $table->unsignedBigInteger('default_location_id')->nullable();
            $table->string('default_shelf_level')->nullable();
            $table->string('default_slot_number')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('source')->default('master');
            $table->timestamps();
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('component_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();
        });

        Department::create(['code' => '11', 'name' => 'Dept Gudang']);
        Department::create(['code' => '01', 'name' => 'Dept Laser Cutting']);
        Warehouse::create(['code' => 'WH-01', 'name' => 'Gudang Utama']);
    }

    public function test_work_request_create_serializes_master_components_with_summed_stock_and_zero_smb_io(): void
    {
        config(['services.components.photo_dir' => 'Q:\\NonExistent\\Drive\\Path']);

        Permission::create(['name' => 'work-request.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'work-request.create', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Operator Gudang',
            'username' => 'op_gudang_perf',
            'department' => 'Dept Gudang',
            'work_area' => 'gudang',
            'password' => bcrypt('password'),
        ]);
        $user->givePermissionTo(['work-request.view', 'work-request.create']);

        $loc = Location::create([
            'zone_code' => '1',
            'rack_number' => '01',
            'rack_code' => 'R1',
        ]);

        $masterComp = Component::create([
            'name' => 'BRACKET UTAMA TEST',
            'part_number' => 'MASTER-PERF-001',
            'uom' => 'PCS',
            'minimum_stock' => 10,
            'default_location_id' => $loc->id,
            'default_shelf_level' => 'L2',
            'default_slot_number' => '03',
            'source' => 'master',
            'is_active' => true,
        ]);

        StockBalance::create(['component_id' => $masterComp->id, 'location_id' => $loc->id, 'quantity' => 12]);
        StockBalance::create(['component_id' => $masterComp->id, 'location_id' => $loc->id, 'quantity' => 8]);

        // Ad-hoc WR component must remain 100% isolated from WR master catalog
        Component::create([
            'name' => 'ADHOC LEWAT AJA',
            'part_number' => 'NEW-20261126-ABCD',
            'uom' => 'PCS',
            'source' => 'wr_adhoc',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('work-requests.create'));

        $response->assertStatus(200);
        $response->assertSee('MASTER-PERF-001');
        $response->assertSee('BRACKET UTAMA TEST');
        $response->assertSee('1-R1-L2-03');
        $response->assertSee('photo-stream');
        $response->assertDontSee('NEW-20261126-ABCD');

        // Verify withSum total_stock and zero-I/O image_url accuracy passed into view
        $viewComponents = $response->viewData('components');
        $this->assertCount(1, $viewComponents);
        $this->assertEquals(20.0, $viewComponents->first()->total_stock);
        $this->assertStringContainsString('/components/' . $masterComp->id . '/photo-stream', $viewComponents->first()->image_url);
    }

    public function test_photo_stream_uses_must_revalidate_cache_header_and_supports_etag_304(): void
    {
        Permission::create(['name' => 'work-request.view', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'Viewer Gudang',
            'username' => 'viewer_stream',
            'department' => 'Dept Gudang',
            'work_area' => 'gudang',
            'password' => bcrypt('password'),
        ]);

        $pn = 'STREAM-ETAG-' . uniqid();
        $mirrorPath = public_path("uploads/components/{$pn}.jpg");
        if (!is_dir(dirname($mirrorPath))) {
            @mkdir(dirname($mirrorPath), 0777, true);
        }
        file_put_contents($mirrorPath, str_repeat('Z', 120));

        $comp = Component::create([
            'name' => 'Part Stream Test',
            'part_number' => $pn,
            'uom' => 'PCS',
            'source' => 'master',
            'is_active' => true,
        ]);

        try {
            $res1 = $this->actingAs($user)->get(route('components.photo-stream', ['component' => $comp->id]));
            $res1->assertStatus(200);
            $cacheControl = (string) $res1->headers->get('Cache-Control');
            $this->assertStringContainsString('must-revalidate', $cacheControl);
            $this->assertStringContainsString('max-age=300', $cacheControl);

            $etag = $res1->headers->get('ETag');
            $this->assertNotEmpty($etag);

            $res2 = $this->actingAs($user)
                ->withHeaders(['If-None-Match' => $etag])
                ->get(route('components.photo-stream', ['component' => $comp->id]));
            $res2->assertStatus(304);
        } finally {
            @unlink($mirrorPath);
        }
    }
}
