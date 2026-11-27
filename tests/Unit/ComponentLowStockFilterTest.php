<?php

namespace Tests\Unit;

use App\Models\Component;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ComponentLowStockFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('uom')->default('Pcs');
            $table->integer('minimum_stock')->default(0);
            $table->unsignedBigInteger('default_location_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('source', 20)->default('master');
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('rack_code')->nullable();
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

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('component_id');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function test_low_stock_filter_returns_paginator_and_filters_correctly(): void
    {
        $user = User::create([
            'name' => 'Admin Gudang',
            'email' => 'admin@hpk.test',
            'password' => bcrypt('password'),
        ]);

        // Komponen 1: Kritis (Stok 5 < Min 10)
        $compLow = Component::create([
            'part_number' => 'PART-LOW-01',
            'name' => 'Baut Roda Kritis',
            'minimum_stock' => 10,
            'is_active' => true,
            'source' => 'master',
        ]);
        StockBalance::create([
            'component_id' => $compLow->id,
            'quantity' => 5,
        ]);

        // Komponen 2: Pas di Batas Minimum (Stok 10 == Min 10 -> Aman sesuai aturan < sistem lama)
        $compEqual = Component::create([
            'part_number' => 'PART-EQUAL-01',
            'name' => 'Bracket Pas Minimum',
            'minimum_stock' => 10,
            'is_active' => true,
            'source' => 'master',
        ]);
        StockBalance::create([
            'component_id' => $compEqual->id,
            'quantity' => 10,
        ]);

        // Komponen 3: Aman (Stok 20 > Min 10)
        $compSafe = Component::create([
            'part_number' => 'PART-SAFE-01',
            'name' => 'Plat Besi Aman',
            'minimum_stock' => 10,
            'is_active' => true,
            'source' => 'master',
        ]);
        StockBalance::create([
            'component_id' => $compSafe->id,
            'quantity' => 20,
        ]);

        // Komponen 4: Tanpa minimum stock (Min 0, Stok 0 -> bukan kategori low stock)
        $compZero = Component::create([
            'part_number' => 'PART-ZERO-01',
            'name' => 'Aksesoris Tanpa Min',
            'minimum_stock' => 0,
            'is_active' => true,
            'source' => 'master',
        ]);

        $this->assertTrue($compLow->fresh()->is_low_stock);
        $this->assertFalse($compEqual->fresh()->is_low_stock);
        $this->assertFalse($compSafe->fresh()->is_low_stock);
        $this->assertFalse($compZero->fresh()->is_low_stock);

        $this->actingAs($user);
        $controller = new \App\Http\Controllers\ComponentController();
        $request = new Request(['low_stock' => '1']);

        $renderedComponentsHtml = (string) $controller->index($request);
        $this->assertStringContainsString('PART-LOW-01', $renderedComponentsHtml);
        $this->assertStringNotContainsString('PART-EQUAL-01', $renderedComponentsHtml);
        $this->assertStringNotContainsString('PART-SAFE-01', $renderedComponentsHtml);
        $this->assertStringNotContainsString('PART-ZERO-01', $renderedComponentsHtml);

        // Pastikan scopeLowStock & LengthAwarePaginator pada model/query konsisten tepat 1
        $paginator = Component::masterCatalog()->lowStock()->paginate(15);
        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(1, $paginator->total());
        $this->assertEquals($compLow->id, $paginator->items()[0]->id);

        // Pastikan WorkRequestController::safetyStock() juga konsisten tepat 1 (hanya compLow)
        $wrController = new \App\Http\Controllers\WorkRequestController();
        $renderedSafetyStockHtml = (string) $wrController->safetyStock(new Request(['_live' => '1']));
        $this->assertStringContainsString('PART-LOW-01', $renderedSafetyStockHtml);
        $this->assertStringNotContainsString('PART-EQUAL-01', $renderedSafetyStockHtml);
        $this->assertStringNotContainsString('PART-SAFE-01', $renderedSafetyStockHtml);
        $this->assertStringNotContainsString('PART-ZERO-01', $renderedSafetyStockHtml);
    }
}
