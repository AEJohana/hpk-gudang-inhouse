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
            $table->string('email')->unique();
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
            $table->timestamps();
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

        // Komponen 1: Kritis (Stok 5 <= Min 10)
        $compLow = Component::create([
            'part_number' => 'PART-LOW-01',
            'name' => 'Baut Roda Kritis',
            'minimum_stock' => 10,
            'is_active' => true,
        ]);
        StockBalance::create([
            'component_id' => $compLow->id,
            'quantity' => 5,
        ]);

        // Komponen 2: Aman (Stok 20 > Min 10)
        $compSafe = Component::create([
            'part_number' => 'PART-SAFE-01',
            'name' => 'Plat Besi Aman',
            'minimum_stock' => 10,
            'is_active' => true,
        ]);
        StockBalance::create([
            'component_id' => $compSafe->id,
            'quantity' => 20,
        ]);

        // Komponen 3: Tanpa minimum stock (Min 0, Stok 0 -> bukan kategori low stock)
        $compZero = Component::create([
            'part_number' => 'PART-ZERO-01',
            'name' => 'Aksesoris Tanpa Min',
            'minimum_stock' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $controller = new \App\Http\Controllers\ComponentController();
        $request = new Request(['low_stock' => '1']);

        $viewResponse = $controller->index($request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $viewResponse);
        $this->assertEquals('master_components.index', $viewResponse->name());

        $components = $viewResponse->getData()['components'];

        // Pastikan bertipe LengthAwarePaginator (bukan Collection murni)
        $this->assertInstanceOf(LengthAwarePaginator::class, $components);

        // Pastikan method total() ada dan nilainya tepat 1 (hanya compLow)
        $this->assertEquals(1, $components->total());
        $this->assertEquals($compLow->id, $components->items()[0]->id);
    }
}
