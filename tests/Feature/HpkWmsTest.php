<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Component;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HpkWmsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::where('role', 'admin_gudang')->first() ?? User::first();
    }

    public function test_login_page_renders_with_hpk_branding(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('HYDRAXLE PERKASA');
        $response->assertSee('logo_hpk.webp');
    }

    public function test_dashboard_renders_command_center(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('WAREHOUSE MANAGEMENT SYSTEM');
        $response->assertSee('ZONA A');
        $response->assertSee('ZONA B');
    }

    public function test_components_index_and_search_api(): void
    {
        $response = $this->actingAs($this->user)->get('/components');
        $response->assertStatus(200);
        $response->assertSee('HYD-CYL-160');

        $apiResponse = $this->actingAs($this->user)->getJson('/api/components/search?q=HYD-CYL-160');
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonPath('success', true);
        $apiResponse->assertJsonPath('data.part_number', 'HYD-CYL-160');
    }

    public function test_transactions_index_and_create(): void
    {
        $response = $this->actingAs($this->user)->get('/transactions');
        $response->assertStatus(200);

        $createResponse = $this->actingAs($this->user)->get('/transactions/create');
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Identifikasi Komponen');
    }

    public function test_ecrs_module(): void
    {
        $response = $this->actingAs($this->user)->get('/ecrs');
        $response->assertStatus(200);
        $response->assertSee('ECR-2026-10-001');
    }

    public function test_disposals_module(): void
    {
        $response = $this->actingAs($this->user)->get('/disposals');
        $response->assertStatus(200);
        $response->assertSee('DSP-2026-10-001');
    }

    public function test_qr_requests_and_thermal_print(): void
    {
        $response = $this->actingAs($this->user)->get('/qr-requests');
        $response->assertStatus(200);
        $response->assertSee('QR-REQ-202610-001');
    }

    public function test_cycle_counts_module(): void
    {
        $response = $this->actingAs($this->user)->get('/cycle-counts');
        $response->assertStatus(200);
        $response->assertSee('CC-202610-001');
    }

    public function test_warehouse_map_module(): void
    {
        $response = $this->actingAs($this->user)->get('/warehouse-map');
        $response->assertStatus(200);
        $response->assertSee('ZONA A');
        $response->assertSee('Raw Material Baja');
    }
}
